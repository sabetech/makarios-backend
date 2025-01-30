<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\Arrival;
use Illuminate\Http\Request;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;


class ArrivalController extends BaseController
{
    //
    public function create(Request $request) {
        $date = $request->get('date');
        $bacenta_id = $request->get('bacenta_id');

        $imageUrl = null;
        if ($request->hasFile('picture')) {
            $file = $request->file('picture');
            $result = Cloudinary::upload($file->getRealPath(), [
                'folder' => 'arrivals',
                'public_id' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                'overwrite' => false,
                'resource_type' => 'image',
                'transformation' => [
                    'quality' => 'auto:eco',
                    'fetch_format' => 'auto',
                ]
            ]);

            $imageUrl = $result->getSecurePath();
        }

        if (!$imageUrl) {
            return $this->sendError('Image not found', ['error' => 'Image Could not be uploaded, Please Try again'], 404);
        }

        $arrival = Arrival::create([
            'date' => $date,
            'time' => date('H:i:s'),
            'bacenta_id' => $bacenta_id,
            'number_bussed' => $request->get('number_bussed'),
            'img_proof' => $imageUrl,
        ]);

        if ($arrival){
            return $this->sendResponse($arrival, 'Arrival created successfully');
        }
        return $this->sendError('Arrival not created', ['error' => 'Arrival Could not be created, Please Try again'], 404);
    }

}
