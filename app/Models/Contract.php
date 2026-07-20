<?php

namespace App\Models;

use App\Models\Concerns\AdminSearchableContract;
use App\Models\Payment;
use App\Models\UnitsReal;
use App\Models\UsageUnit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;
use TaqnyatSms;

class Contract extends Model
{
    use AdminSearchableContract;
    use HasFactory;

    public const SKIP_INITIAL_STEPS_INSTRUMENT_TYPES = [
        'lease_renewal',
        'sublease_agreement',
    ];

    public const CONTRACT_TYPES = [
        'housing',
        'commercial',
    ];

    public static function contractTypes(): array
    {
        return self::CONTRACT_TYPES;
    }

    /**
     * @return list<array{key: string, name: string, name_ar: string, name_en: string}>
     */
    public static function contractTypeOptions(): array
    {
        return array_map(
            static fn (string $key): array => [
                'key' => $key,
                'name' => self::contractTypeLabel($key),
                'name_ar' => self::contractTypeLabel($key, 'ar'),
                'name_en' => self::contractTypeLabel($key, 'en'),
            ],
            self::contractTypes()
        );
    }

    public static function contractTypeLabel(string $contractType, ?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        if ($locale === 'en') {
            return match ($contractType) {
                'housing' => 'Housing',
                'commercial' => 'Commercial',
                default => $contractType,
            };
        }

        return match ($contractType) {
            'housing' => 'سكني',
            'commercial' => 'تجاري',
            default => $contractType,
        };
    }

    public static function instrumentTypes(): array
    {
        return RealEstate::instrumentTypes();
    }

    public static function shouldSkipInitialSteps(?string $instrumentType): bool
    {
        return in_array((string) $instrumentType, self::SKIP_INITIAL_STEPS_INSTRUMENT_TYPES, true);
    }

    /**
     * Map API / UI aliases to canonical enum values stored on contracts.instrument_type.
     */
    public static function normalizeInstrumentType(?string $value): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        $normalized = strtolower(trim($value));

        $aliases = [
            'electronic_deed_from_the_ministry_of_justice' => 'electronic',
            'electronic_deed' => 'electronic',
            'electronic_deed_from_ministry_of_justice' => 'electronic',
        ];

        $canonical = $aliases[$normalized] ?? $normalized;

        return in_array($canonical, self::instrumentTypes(), true) ? $canonical : $value;
    }

    /*
    |--------------------------------------------------------------------------
    | GLOBAL VARIABLES
    |--------------------------------------------------------------------------
    */
 
    protected $guarded = ['id'];

    protected $casts = [
        'tenant_role_ids' => 'array',
        'kitchen_tank' => 'boolean',
        'furnished' => 'boolean',
        'accept_retrun_contract' => 'boolean',
        'is_draft' => 'boolean',
    ];

    protected $appends = [
        'created_at_label',
        'strong_argument_photo_path',
        'copy_of_the_authorization_or_agency_path',
        'contract_type_trans',
        'instrument_type_trans',
        'draft_before_paid_path',
        'draft_after_paid_path',
        'total_price',
        'dob_of_property_owner_agent',
     ];

    /*
    |--------------------------------------------------------------------------
    | BOOT
    |--------------------------------------------------------------------------
    */
    
     public static function boot()
    {
        parent::boot();
    
        self::creating(function ($model) {
            $model->uuid = self::generateUUID();

            if ($model->app_or_web === null || $model->app_or_web === '') {
                $model->app_or_web = 'app';
            }

            // أي عقد جديد (بما فيها تجديد الإيجار) يبدأ بحالة "جديد" حتى يتم استلامه.
            if (empty($model->contract_status_id)
                && ContractStatus::query()->whereKey(ContractStatus::NEW_ID)->exists()) {
                $model->contract_status_id = ContractStatus::NEW_ID;
            }
        });

        self::saving(function (Contract $model): void {
            if ($model->isDirty('tenant_role_ids')) {
                $ids = $model->tenant_role_ids;
                $normalized = is_array($ids)
                    ? array_values(array_unique(array_filter(array_map(static fn ($v) => (int) $v, $ids))))
                    : [];
                $model->tenant_role_ids = $normalized !== [] ? $normalized : null;
                $model->tenant_role_id = $normalized[0] ?? null;

                return;
            }

            if ($model->isDirty('tenant_role_id')) {
                $tid = $model->tenant_role_id;
                $model->tenant_role_ids = $tid !== null && $tid !== '' ? [(int) $tid] : null;
            }
        });
    }

    /**
     * Active contracts (not soft-deleted).
     */
    public function scopeNotDeleted($query)
    {
        return $query->where('is_delete', 0);
    }

    /**
     * Contracts that reached at least the given step (default: 3).
     * Used by admin order lists and API v2 contract lists.
     */
    public function scopeReachedAdminOrderStep($query, int $minStep = 3)
    {
        return $query->where('step', '>=', $minStep);
    }

    /**
     * Incomplete contracts: is_completed = 0.
     */
    public function scopeIncomplete($query)
    {
        return $query->where('is_completed', 0);
    }

    /**
     * Completed contracts: is_completed = 1.
     */
    public function scopeCompleted($query)
    {
        return $query->where('is_completed', 1);
    }

    /**
     * Draft contracts: is_draft = 1.
     */
    public function scopeDraft($query)
    {
        return $query->where('is_draft', true);
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS (virtual attributes)
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | FUNCTIONS
    |--------------------------------------------------------------------------
    */
 
    public function getContractAttribute()
    {
      
        $contractsToDelete = $this->where('is_delete', 0)
            // ->where('step', '>', 6)
            ->where(function ($query) {
                $query->where('is_completed', 0)
                    ->orderBy('updated_at', 'desc')
                    ->where('created_at', '<', now()->subDays(7));
            })
            ->get();
    
        foreach ($contractsToDelete as $contract) {
            $contract->update(['is_delete' => 1]);
        }
    
       
        $query = $this->where('is_delete', 0)->where('step','>=',5);
    
        $query->when(Request::filled('uuid'), function ($q) {
            $q->where('uuid', 'like', '%' . Request::get('uuid') . '%');
        });
    
        // Filter by contract ownership
        $query->when(Request::filled('contract_ownership'), function ($q) {
            $q->where('contract_ownership', 'like', '%' . Request::get('contract_ownership') . '%');
        });
    
        // Filter by contract type
        $query->when(Request::filled('contract_type'), function ($q) {
            $q->where('contract_type', 'like', '%' . Request::get('contract_type') . '%');
        });
    
        // Fetch and paginate results
        $result = $query->orderBy('id', 'desc')->paginate(10);
    
        return $result;
    }
    //reset contract
    public function getContractDeleteAttribute()
    {
 

     $query = $this->where('is_delete', 1) ;
 

        if (!empty(Request::get('uuid'))) {
            $query = $query->where('uuid', 'like', '%' . Request::get('uuid') . '%');
        }

        if (!empty(Request::get('contract_ownership'))) {
            $query = $query->where('contract_ownership', 'like', '%' . Request::get('contract_ownership') . '%');
        }

        if (!empty(Request::get('contract_type'))) {
            $query = $query->where('contract_type', 'like', '%' . Request::get('contract_type') . '%');
        }

      
        $result = $query->orderBy('contracts.id', 'desc')
                       ->paginate(10);

        return $result;
    }

    public static function generateUUID()
    {
        $uuids = self::select('uuid')->get()->pluck('uuid')->toArray();
        $uuid = random_int(100000, 999999);
       

        while (in_array($uuid, $uuids)) {
            $uuid = random_int(100000, 999999);
        }
        return intval($uuid);
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function realEstate()
    {
        return $this->belongsTo(RealEstate::class, 'real_id');
    }

    /** Units count: from linked real estate when real_id is set, else contract column. */
    public function numberOfUnitsInRealestate(): mixed
    {
        if ($this->real_id) {
            $this->loadMissing('realEstate');

            return $this->realEstate?->number_of_units_in_realestate ?? $this->number_of_units_in_realestate;
        }

        return $this->number_of_units_in_realestate;
    }

     
    public function propertyType()
    {
        return $this->belongsTo(ReaEstatType::class, 'property_type_id');
    }

    public function propertyUsages()
    {
        return $this->belongsTo(ReaEstatUsage::class, 'property_usages_id');
    }

    public function propertyCity()
    {
        return $this->belongsTo(City::class, 'property_city_id');
    }

    
    public function propertyRegion()
    {
        return $this->belongsTo(Region::class, 'property_place_id');
    }
    

    public function tenantEntityLegalRegion()
    {
        return $this->belongsTo(City::class, 'region_of_the_tenant_legal_agent');
    }

    public function unit()
    {
        return $this->belongsTo(UnitsReal::class, 'real_units_id');
    }

    /**
     * Units linked to this contract via contract_units (one or more).
     */
    public function units()
    {
        return $this->belongsToMany(
            UnitsReal::class,
            'contract_units',
            'contract_id',
            'real_unit_id'
        )->withPivot(['real_estate_id'])->withTimestamps();
    }

    public function contractUnits()
    {
        return $this->hasMany(ContractUnit::class, 'contract_id');
    }

    public function tenantEntityLegalCity()
    {
        return $this->belongsTo(City::class, 'city_of_the_tenant_legal_agent');
    }

   
  

    public function tenantEntityCity()
    {
        return $this->belongsTo(City::class, 'tenant_entity_city_id');
    }

    public function tenantEntityRegion()
    {
        return $this->belongsTo(Region::class, 'tenant_entity_region_id');
    }

    public function unitType()
    {
        return $this->belongsTo(UnitType::class, 'unit_type_id');
    }


    public function unitUsage()
    {
        return $this->belongsTo(UsageUnit::class, 'unit_usage_id');
    }

    public function contractTermInYears()
    {
        return $this->belongsTo(ContractPeriod::class, 'contract_term_in_years');
    }

    public function paymentType()
    {
        return $this->belongsTo(PaymentType::class, 'payment_type_id');
    }

    public function tenantRole()
    {
        return $this->belongsTo(TenantRole::class, 'tenant_role_id');
    }

    /**
     * Payments linked by contract UUID (see payments.contract_uuid).
     */
    public function contractPayments()
    {
        return $this->hasMany(Payment::class, 'contract_uuid', 'uuid');
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESORS
    |--------------------------------------------------------------------------
    */

    public function getCreatedAtLabelAttribute()
    {
        return date('Y-m-d H:i A', strtotime($this->created_at));
    }
    public function getUpdateAtLabelAttribute()
    {
        return date('Y-m-d H:i A', strtotime($this->updated_at));
    }

  

   
    
    public function getCopyOfTheoOwnerRecord()
    {
        return getFilePath($this->copy_of_the_owner_record);
    }

    


    public function getStrongArgumentPhotoPathAttribute()
    {
        return getFilePath($this->strong_argument_photo);
    }
    
     
    public function getPhotoOfElectronic()
    {
        return getFilePath($this->photo_of_the_electronic);
    }
    


    public function getCopyOfTheAuthorizationOrAgencyPathAttribute()
    {

        return getFilePath($this->copy_of_the_authorization_or_agency);
    }

    public function getContractTypeTransAttribute()
    {
        return self::contractTypeLabel((string) $this->contract_type);
    }

    /**
     * API alias for DB column `dob_hijri_of_property_owner_agent`.
     */
    public function getDobOfPropertyOwnerAgentAttribute(mixed $value): mixed
    {
        if ($value !== null && $value !== '') {
            return $value;
        }

        return $this->attributes['dob_hijri_of_property_owner_agent'] ?? null;
    }

    public function setDobOfPropertyOwnerAgentAttribute(mixed $value): void
    {
        $this->attributes['dob_hijri_of_property_owner_agent'] = $value;
    }

    public function getInstrumentTypeTransAttribute()
    {
        return self::instrumentTypeLabel((string) $this->instrument_type);
    }

    public static function instrumentTypeLabel(string $instrumentType, ?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        if ($locale === 'en') {
            return match ($instrumentType) {
                'electronic' => 'Electronic deed',
                'electronic_tax_register' => 'Electronic tax register',
                'property_ownership_owner_are_deceased_endowment' => 'Owner deceased endowment ownership deed',
                'property_ownership_owner_is_endowment' => 'Ownership deed; property owner is endowment (waqf)',
                'sale_agreement' => 'Sale agreement',
                'electronic_deed_from_the_ministry_of_justice' => 'Electronic deed from Ministry of Justice',
                'economic_cities_authority_suspended' => 'Economic Cities Authority (suspended)',
                'property_ownership_owner_are_deceased' => 'Owner deceased property ownership deed',
                'property_ownership_owner_are_suspended' => 'Ownership deed (owner suspended)',
                'old_handwritten' => 'Old handwritten deed',
                'strong_argument' => 'Strong argument deed',
                'sublease_agreement' => 'Sublease agreement',
                'lease_renewal' => 'Lease renewal',
                default => $instrumentType,
            };
        }

        return match ($instrumentType) {
            'electronic' => 'صك إلكتروني',
            'electronic_tax_register' => 'سجل ضريبي إلكتروني',
            'property_ownership_owner_are_deceased_endowment' => 'صك ملكية وقف لمالك متوفى',
            'property_ownership_owner_is_endowment' => 'صك ملكية ومالك العقار وقف',
            'sale_agreement' => 'عقد بيع',
            'electronic_deed_from_the_ministry_of_justice' => 'صك إلكتروني من وزارة العدل',
            'economic_cities_authority_suspended' => 'هيئة المدن الاقتصادية (معلق)',
            'property_ownership_owner_are_deceased' => 'صك ملكية لمالك متوفى',
            'property_ownership_owner_are_suspended' => 'صك ملكية (مالك موقوف)',
            'old_handwritten' => 'صك يدوي قديم',
            'strong_argument' => 'صك ملكية إلكتروني من السجل العقاري',
            'sublease_agreement' => 'اتفاقية إعارة من الباطن',
            'lease_renewal' => 'تجديد عقد إيجار',
            default => $instrumentType,
        };
    }

    /**
     * @return list<array{type: string, key: string, name_ar: string, images: list<array{key: string, name_ar: string, name_en: string}>, conditional_images: list<array{key: string, name_ar: string, name_en: string, when: array<string, mixed>}>, required_images: list<array{key: string, name_ar: string, name_en: string}>, conditional_required_images: list<array{key: string, name_ar: string, name_en: string, when: array<string, mixed>}>}>
     */
    public static function instrumentTypeOptions(): array
    {
        return array_map(static function (string $key): array {
            $requirements = self::instrumentTypeImageRequirements($key);

            return [
                'type' => $key,
                'key' => $key,
                'name_ar' => self::instrumentTypeLabel($key, 'ar'),
                'images' => $requirements['required_images'],
                'conditional_images' => $requirements['conditional_required_images'],
                'required_images' => $requirements['required_images'],
                'conditional_required_images' => $requirements['conditional_required_images'],
            ];
        }, self::instrumentTypes());
    }

    /**
     * @return array{
     *     required_images: list<array{key: string, name_ar: string, name_en: string}>,
     *     conditional_required_images: list<array{key: string, name_ar: string, name_en: string, when: array<string, mixed>}>
     * }
     */
    public static function instrumentTypeImageRequirements(?string $instrumentType): array
    {
        $requirements = [
            'required_images' => self::requiredImagesForInstrumentType($instrumentType),
            'conditional_required_images' => self::conditionalRequiredImagesForInstrumentType($instrumentType),
        ];

        return array_merge($requirements, [
            'type' => $instrumentType,
            'images' => $requirements['required_images'],
            'conditional_images' => $requirements['conditional_required_images'],
        ]);
    }

    /**
     * @return list<string>
     */
    public static function deedFrontBackImageFields(): array
    {
        return [
            'image_instrument_from_the_front',
            'image_instrument_from_the_back',
        ];
    }

    /**
     * Deed image fields required for a given instrument type.
     *
     * @return list<string>
     */
    public static function deedImageFieldsForInstrumentType(?string $instrumentType): array
    {
        // Contract deed/document images are optional for all instrument types.
        return [];
    }

    /**
     * @return list<array{key: string, name_ar: string, name_en: string}>
     */
    public static function requiredImagesForInstrumentType(?string $instrumentType): array
    {
        return [];
    }

    /**
     * @return list<array{key: string, name_ar: string, name_en: string, when: array<string, mixed>}>
     */
    public static function conditionalRequiredImagesForInstrumentType(?string $instrumentType): array
    {
        return [];
    }

    public static function instrumentImageFieldLabel(string $field, ?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        $labels = [
            'image_instrument' => ['ar' => 'صورة الصك', 'en' => 'Deed image'],
            'image_instrument_from_the_front' => ['ar' => 'صورة الصك من الأمام', 'en' => 'Deed front image'],
            'image_instrument_from_the_back' => ['ar' => 'صورة الصك من الخلف', 'en' => 'Deed back image'],
            'copy_of_the_endowment_registration_certificate' => ['ar' => 'شهادة تسجيل الوقف', 'en' => 'Endowment registration certificate'],
            'copy_of_the_trusteeship_deed' => ['ar' => 'صك النظارة', 'en' => 'Trusteeship deed'],
            'copy_of_guardians_power_of_attorney_for_agent' => ['ar' => 'وكالة النظار للوكيل', 'en' => 'Guardians power of attorney'],
            'image_address' => ['ar' => 'صورة العنوان', 'en' => 'Address image'],
        ];

        $lang = $locale === 'en' ? 'en' : 'ar';

        return $labels[$field][$lang] ?? $field;
    }
    public function getDraftBeforePaidPathAttribute()
    {
        return isset($this->draft_before_paid) ? getFilePath($this->draft_before_paid) : '';
    }

    public function getDraftAfterPaidPathAttribute()
    {
        return isset($this->draft_before_paid) ? getFilePath($this->draft_after_paid) : '';
    }

    
    public function getTotalPriceAttribute()
    {
        $contract=Contract::get();
        $setting = Setting::first();
        $app_fees = $setting ? $setting->application_fees : '';
        // $contract_period_price = $this->contractTermInYears?->price;
        if ($this->contract_type == 'housing') {
            $tax = $setting ? $setting->housing_tax : '';
            $tax_name = "Residential_contract_tax";
        } 
        
        else 
        {
            $tax = $setting ? $setting->commercial_tax : '';
            $tax_name = "Value_added_tax";
        }

        $total_price = intval($app_fees) + intval($tax);

        $details = [
            'Application_fees' => $app_fees,
            // 'Contract_period' => $contract_period_price,
            $tax_name => $tax
        ];

        return [
            'details' => $details,
            'total_price' => $total_price
        ];
    }
    
    public function getServicesAttribute()
    {
        return ServicesPricing::where('contract_type', $this->contract_type)->get();
    }
    public function getPriceContractAttribute()
    {
        // $accountsHandwrite = Account::first();
        
        $contractPeriodPrice = ContractPeriod::where('id', $this->id)->value('price') ?? 0;
      
        $contractType = $this->contract_type;         
        $services = ServicesPricing::where('contract_type', $contractType)
            ->get(['name_ar', 'price'])
            ->map(function ($service) {
                return [
                    'service_name' => $service->name_ar,
                    'service_price' => $service->price,
                ];
            })->toArray();
 
        $setting = Setting::first();
        $appFees = $setting ? intval($setting->application_fees) : 0;
    
         if ($this->contract_type == 'housing') {
            $tax = $setting ? intval($setting->housing_tax) : 0;
        } else {
            $tax = $setting ? intval($setting->commercial_tax) : 0;
        }
            
         $servicesTotalPrice = array_sum(array_column($services, 'service_price'));
      
         $totalContractPrice =$contractPeriodPrice + $servicesTotalPrice + $appFees + $tax;

     }
    
    
    

    
    static public function getSingle($id)
    {
        return User::find($id);
    }

    public function payments()
    {
        return $this->belongsToMany(Payment::class);
    }

    public function receivedContract()
    {
        return $this->hasOne(ReceivedContract::class, 'contract_id');
    }

    /**
     * Relationship: Contract belongs to a ContractStatus
     */
    public function contractStatus()
    {
        return $this->belongsTo(ContractStatus::class, 'contract_status_id');
    }

    public function draftContractStatus()
    {
        return $this->belongsTo(DraftContractStatus::class, 'draft_contract_status_id');
    }

    public function acceptRetrunContractEmployee()
    {
        return $this->belongsTo(Employee::class, 'accept_retrun_contract_employee_id');
    }

    public function refundableContract()
    {
        return $this->hasOne(RefundableContract::class, 'contract_id')->latestOfMany();
    }

    public function contractPaidByEmployees()
    {
        return $this->hasMany(ContractPaidByEmployee::class, 'contract_uuid', 'uuid');
    }

    public function comments()
    {
        return $this->hasMany(ContractComment::class, 'contract_id');
    }

       /*
    |--------------------------------------------------------------------------
    | Scope Contract Review
    |--------------------------------------------------------------------------
    */

 
    public function scopeGetCompeleteContract($query,$uuids)
    {
     return $query=Contract::whereIn('uuid',$uuids)->where('is_completed',1)->where('is_review',0);
    } 
    
    public function scopeGetPaymentContract($query)
    {
        return $query=Payment::where('status', 'success')->pluck('contract_uuid');
    }

    
    public function scopeGetReview($query)
    {
        return $query=Contract::where('is_review', true) ;
    }

    protected static bool $documentationOffsetDaysLoaded = false;

    protected static ?int $documentationOffsetDaysValue = null;

    /** Days from settings; added to contract created_at for documentation deadline. */
    public static function documentationOffsetDays(): ?int
    {
        if (! static::$documentationOffsetDaysLoaded) {
            $raw = Setting::value('time_to_documentation_contract');
            static::$documentationOffsetDaysValue = $raw === null || $raw === '' ? null : (int) $raw;
            static::$documentationOffsetDaysLoaded = true;
        }

        return static::$documentationOffsetDaysValue;
    }

    public function documentationDeadlineAt(): ?\Illuminate\Support\Carbon
    {
        $days = static::documentationOffsetDays();
        if ($days === null || $this->created_at === null) {
            return null;
        }

        return $this->created_at->copy()->addDays($days);
    }
}
 
