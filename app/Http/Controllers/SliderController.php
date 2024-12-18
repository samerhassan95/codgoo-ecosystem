<?php
namespace App\Http\Controllers;

use App\Http\Requests\SliderRequest;
use App\Http\Resources\ProductResource;
use App\Http\Resources\SliderResource;
use App\Models\Slider;
use App\Repositories\SliderRepositoryInterface;
use App\Services\ImageService;

class SliderController extends Controller
{
    private $sliderRepository;

    public function __construct(SliderRepositoryInterface $sliderRepository)
    {
        $this->sliderRepository = $sliderRepository;
    }

    public function index()
    {
        $sliders = $this->sliderRepository->all();
        return SliderResource::collection($sliders);
    }

    public function store(SliderRequest $request)
    {
        $slider = $this->sliderRepository->create([
            'name' => $request->name,
        ]);
    
        // Attach Products
        $attachments = [];
        foreach ($request->products as $product) {
            $imagePath = ImageService::upload($product['image'], 'slider_products');
            $attachments[$product['id']] = ['image' => $imagePath];
        }
    
        $slider->products()->attach($attachments);
    
        return new SliderResource($slider);
    }
    

    public function show(Slider $slider)
    {
        // Load products with pivot image
        $slider->load(['products' => function ($query) {
            $query->withPivot('image'); // Include pivot image
        }]);

        // Map the products with ProductResource and pivot image
        $productsWithImages = $slider->products->map(function ($product) {
            return [
                'product' => new ProductResource($product), // Wrap each product in ProductResource
                'slider_image' => $product->pivot ? asset($product->pivot->image) : null, // Include pivot image
            ];
        });

        return response()->json([
            'status' => true,
            'message' => 'Slider retrieved successfully.',
            'data' => [
                'id' => $slider->id,
                'name' => $slider->name,
                'products' => $productsWithImages,
            ],
        ]);
    }

    

    public function destroy($id)
    {
        $this->sliderRepository->delete($id);
        return response()->json(['message' => 'Slider deleted successfully']);
    }
}
