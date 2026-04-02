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
    $paths = [];

    // 1. Loop through the multiple files
    if ($request->hasFile('image')) {
        foreach ($request->file('image') as $file) {
            // Upload each file and add path to the array
            $paths[] = ImageService::upload($file, 'slider_products');
        }
    }

    // 2. Create the slider with the array of paths
    $slider = Slider::create([
        'product_id' => $request->product_id,
        'image'      => $paths, // This must be an array
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
    return response()->json([
        'status' => true,
        'data' => [
            'id' => $slider->id,
            'images' => array_map(fn($path) => asset($path), $slider->image ?? []),
            'product' => new ProductResource($slider->product),
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
