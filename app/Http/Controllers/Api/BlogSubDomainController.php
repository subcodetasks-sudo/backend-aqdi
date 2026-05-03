<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\V2\BlogIndexRequest;
use App\Http\Requests\Admin\V2\StoreBlogRequest;
use App\Http\Requests\Admin\V2\UpdateBlogRequest;
use App\Http\Resources\BlogResource;
use App\Models\Blog;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;


class BlogSubDomainController extends Controller
{
   

 public function blogs()
    {
        $blogs = $this->getPublishedOrScheduledBlogs();

        if ($blogs->isEmpty()) {
            return response()->json([
                'status' => true,
                'message' => __('api.blog_not_found'),
                'data' => [],
            ], Response::HTTP_OK);
        }

        return response()->json([
            'status' => true,
            'message' => __('api.retrieve_blog'),
            'data' => BlogResource::collection($blogs),
            'pagination' => [
                'total'        => $blogs->total(),
                'per_page'     => $blogs->perPage(),
                'current_page' => $blogs->currentPage(),
                'last_page'    => $blogs->lastPage(),
                'from'         => $blogs->firstItem(),
                'to'           => $blogs->lastItem(),
            ],
        ], Response::HTTP_OK);
    }


    private function getPublishedOrScheduledBlogs()
    {
        $timeNow = now()->setTimezone(config('app.timezone'))->toDateTimeString();

        return  Blog::where(function ($query) use ($timeNow) {
            $query->where(function ($q) {
                $q->where('status', 'published')
             
                ->where('is_active', 1);
            })->orWhere(function ($q) use ($timeNow) {
                $q->where('status', 'schedule')
                ->where('publish_at', '<=', $timeNow)
                ->where('is_active', 1);
            });
        })
        ->latest()
        ->paginate(6);
    }


    public function singleBlog($slug)
    {
        $blog = Blog::where('slug', $slug)->first();
        if (!$blog) {
            return response()->json([
                'status' => false,
                'message' => __('api.blog_not_found'),
                'data' => [],
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'status' => true,
            'message' => __('api.retrieve_blog'),
            'data' => new BlogResource($blog),
        ], Response::HTTP_OK);
    }


     /**
     * Store a newly created blog
     */
    public function store(StoreBlogRequest $request)
    {
        try {
            $data = $request->validated();

            if ($request->hasFile('image')) {
                $data['image'] = fileUploader($request->file('image'), 'blogs');
            }

            $data['is_active'] = $data['is_active'] ?? 0;

            $blog = Blog::create($data);

            return $this->apiResponse(
                new BlogResource($blog),
                trans('api.created_successfully'),
                201
            );

        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), 422);
        } catch (\Throwable $e) {
            return $this->errorMessage(
                trans('api.error_occurred') . ': ' . $e->getMessage(),
                500
            );
        }
    }

    

    /**
     * Update the specified blog
     */
    public function update(UpdateBlogRequest $request, $id)
    {
        try {
            $blog = Blog::findOrFail($id);
            $data = $request->validated();

            if ($request->hasFile('image')) {
                if ($blog->image) {
                    deleteFile($blog->image);
                }
                $data['image'] = fileUploader($request->file('image'), 'blogs');
            }

            $blog->update($data);

            return $this->apiResponse(
                new BlogResource($blog->fresh()),
                trans('api.updated_successfully')
            );

        } catch (ModelNotFoundException) {
            return $this->errorMessage(trans('api.not_found'), 404);
        } catch (\Throwable $e) {
            return $this->errorMessage(
                trans('api.error_occurred') . ': ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Remove the specified blog
     */
    public function destroy($id)
    {
        try {
            $blog = Blog::findOrFail($id);

            if ($blog->image) {
                deleteFile($blog->image);
            }

            $blog->delete();

            return $this->apiResponse([], trans('api.deleted_successfully'));

        } catch (ModelNotFoundException) {
            return $this->errorMessage(trans('api.not_found'), 404);
        }
    }

    /**
     * Toggle active status
     */
    public function toggleActive($id)
    {
        try {
            $blog = Blog::findOrFail($id);

            $blog->update([
                'is_active' => !$blog->is_active
            ]);

            return $this->apiResponse(
                new BlogResource($blog->fresh()),
                trans('api.updated_successfully')
            );

        } catch (ModelNotFoundException) {
            return $this->errorMessage(trans('api.not_found'), 404);
        }
    }

    // Login Seo 

    public function login(Request $request)
{
    $validator = Validator::make($request->all(), [
        'email'    => 'required|email',
        'password' => 'required|string',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status'  => false,
            'message' => 'Validation error',
            'errors'  => $validator->errors(),
        ], 422);
    }

    $credentials = $request->only('email', 'password');

    if (!Auth::guard('seo')->attempt($credentials)) {
        return response()->json([
            'status'  => false,
            'message' => 'يرجى التأكد من البريد الالكتروني وكلمة المرور',
        ], 401);
    }

    /** @var \App\Models\User $user */
    $user = Auth::guard('seo')->user();

    // Create API token (Sanctum)
    $token = $user->createToken('seo-token')->plainTextToken;

    return response()->json([
        'status'  => true,
        'message' => 'تم تسجيل الدخول بنجاح',
        'data'    => [
            'user'  => $user,
            'token' => $token,
        ],
    ], 200);
}
}
