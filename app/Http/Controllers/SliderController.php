<?php
namespace App\Http\Controllers;

use App\Http\Requests\SliderRequest;
use App\Http\Resources\ProductResource;
use App\Http\Resources\SliderResource;
use App\Models\Slider;
use App\Repositories\SliderRepositoryInterface;
use App\Services\ImageService;
use Illuminate\Http\Request;

class SliderController extends Controller
{
    private $sliderRepository;

    public function __construct(SliderRepositoryInterface $sliderRepository)
    {
        $this->sliderRepository = $sliderRepository;
    }

    /**
     * Get all sliders.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $sliders = $this->sliderRepository->all();
        return SliderResource::collection($sliders);
    }

    /**
     * Store a new slider.
     *
     * @param SliderRequest $request
     * @return \Illuminate\Http\Response
     */
    public function store(SliderRequest $request)
    {
        // Create a new slider entry with product_id and image
        $slider = Slider::create([
            'product_id' => $request->product_id,  // Product ID
            'image' => ImageService::upload($request->image, 'slider_products')  // Upload image and store its path
        ]);
    
        return new SliderResource($slider);
    }

    /**
     * Show a specific slider with its product and image.
     *
     * @param Slider $slider
     * @return \Illuminate\Http\Response
     */
    public function show(Slider $slider)
    {
        // Map the product and image data from the slider
        $productWithImage = [
            'product' => new ProductResource($slider->product), // Assuming relationship exists
            'slider_image' => asset($slider->image), // The image related to the slider
        ];

        return response()->json([
            'status' => true,
            'message' => 'Slider retrieved successfully.',
            'data' => [
                'id' => $slider->id,
                'product_id' => $slider->product_id,
                'image' => $productWithImage['slider_image'],
                'product' => $productWithImage['product'],
            ],
        ]);
    }

    /**
     * Delete a slider.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $this->sliderRepository->delete($id);
        return response()->json(['message' => 'Slider deleted successfully']);
    }
}
