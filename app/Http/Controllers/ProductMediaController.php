<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductMediaRequest;
use App\Http\Resources\ProductMediaResource;
use App\Repositories\ProductMediaRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\ProductMedia;

class ProductMediaController extends BaseController
{
    private $repository;

    public function __construct(ProductMediaRepositoryInterface $repository)
    {
        parent::__construct($repository);  
        $this->repository = $repository;
    }

    public function store(Request $request)
    {
        // التحقق من صحة البيانات باستخدام Validator
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'file_path' => 'required|file',
            'type' => 'required|string',
        ]);

        // التحقق من وجود أخطاء
        if ($validator->fails()) {
            // إرجاع رسالة الخطأ كـ JSON إذا فشلت عملية التحقق
            return response()->json(['errors' => $validator->errors()->toArray()], 422);
        }

        // تحقق من رفع الملف
        if ($request->hasFile('file_path')) {
            // تخزين الملف في مجلد product_media داخل storage/app/public
            $filePath = $request->file('file_path')->store('product_media', 'public');
        } else {
            // إذا لم يتم رفع أي ملف
            return response()->json(['message' => 'No file uploaded.'], 400);
        }

        // إضافة المسار المحدث للملف إلى البيانات
        $validatedData = $validator->validated(); // استخدمنا `validated()` للحصول على البيانات الموثقة
        $validatedData['file_path'] = $filePath;

        // إنشاء السجل باستخدام الـ repository
        $productMedia = $this->repository->create($validatedData);

        // إرجاع استجابة باستخدام Resource
        return new ProductMediaResource($productMedia);
    }

    /**
     * Get all media for a specific product.
     *
     * @param  int  $productId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllMediaForProduct($productId)
    {
        // Get all media related to the product
        $media = ProductMedia::where('product_id', $productId)->get();

        // If no media found, return a message
        if ($media->isEmpty()) {
            return response()->json(['message' => 'No media found for this product.'], 404);
        }

        // Return the media data as a resource
        return ProductMediaResource::collection($media);
    }
}
