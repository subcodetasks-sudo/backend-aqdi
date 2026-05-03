@extends('website._layout.app')
@section('content')


    <!-- start main -->
    <main class="InstrumentData">
        <!-- start section  -->
        <head>
    <!-- Other head content -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>

        <section class="fileContract">
            <div class="container">
                <div class="row">
                    <div class="col-11 col-md-6 col-lg-5 m-auto p-4">
                        @if ($contracts->isEmpty())
                            <p>{{ __('website.fileContract.wait') }}</p>
                        @else
                            <div class="title">
                                <h3>{{ __('website.myContract.myContract') }}</h3>
                                @foreach ($contracts as $File)
                                    <a href="{{ Storage::url($File->file) }}" target="_blank">

                                        <button type="submit" name="upload"
                                            id="upload">{{ __('website.fileContract.download') }}<i
                                                class="fa-solid fa-cloud-arrow-down ms-2"></i></button>
                                    </a>
                                @endforeach

                            </div>
                            <!--contractPdf -->

                            <div class="row contractPdf mt-5">
                                <div class="col-12 d-flex">
                                    <img src="{{ asset('website\asset\image\pdfIcon.svg') }}" class="img-fluid"
                                        alt="pdfIcon">
                                    <p>{{ __('website.fileContract.contract_pdf') }}</p>
                                </div>
                            </div>
                        @endif
                        <div class="btns mt-5">
                            @foreach ($contracts as $contract)
                                <form method="GET"
                                    action="{{ url('financial_statements', ['uuid' => $contract->uuid]) }}">
                                    @csrf
                                    <button type="submit">{{ __('website.fileContract.payment') }}</button>
                                </form>
                            @endforeach

                            <button type="submit">{{ __('website.fileContract.edit') }}</button>
                            <form action="https://wa.me/+966597500014">
                                <button type="submit" class="whatsapp-button">
                                    <i class="fab fa-whatsapp"></i> تواصل مع الإدارة عبر الواتساب
                                </button>
                            </form>
                            
                            
                            
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!-- end section  -->

    </main>
    <!-- end main -->



    <!-- Modal  clarification -->
    <!-- Modal -->
    <div class="modal fade modalSendDraft" id="sendDraft" tabindex="-1" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body">
                    <i class="fa-solid fa-exclamation"></i>
                    <h6>تنبيه</h6>
                    <p>أوافق على الدفع بعد ارسال مسودة</p>
                    <div class="btns mt-4">
                        <button type="submit">موافق</button>
                        <button type="button" data-bs-dismiss="modal" aria-label="Close">الغاء</button>

                    </div>
                </div>
            </div>
        </div>
    </div>



@endsection
 <style>
        button {
            display: flex;
            align-items: center;
            background-color: #25D366;
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 16px;
            cursor: pointer;
            border-radius: 5px;
        }

        button i {
            margin-right: 10px;
            font-size: 20px;
        }
    </style>