<?php

namespace App\Http\Controllers\Api;

use App\Models\Analyser;
use App\Models\Location;
use Illuminate\Http\Request;
use App\Models\AnalyserMedia;
use App\Http\Controllers\Controller;

class TicketController extends Controller
{
    public function allTicket(Request $request, $locationID, $m_DateFrom, $m_DateTo)
    {

        $DateFrom = $m_DateFrom;
        $DateTo = $m_DateTo;
        // $DateFrom = '2023-09-23';
        // $DateTo = '2023-09-26';

        $tickets = Analyser::where(['is_download' => 0, 'location_id' => $locationID])->whereDate('capture_at', '>=', $DateFrom)
            ->whereDate('capture_at', '<=', $DateTo)
            ->orderBy('capture_at', 'desc')
            ->take(10)
            ->get();

        $modifiedResponses = [];

        $count = 1;
        foreach ($tickets as $response) {
            $modifiedResponse = [
                "id" => (int) $response['ticket_number'],
                "location_id" => $locationID,
                "time" => $response['capture_at'],
                "data" => "0d27a09cd22264fda6be76423a0b5b8a03a15d759b47da11c11690090d37cd2c88b6bfbec162c54d7f1470197b1e38f6a789bd68b7808c0d493751a94a786755",
                "current_speed_limit" => $response['speedLimit_kph'],
                "violating_speed" => $response['calculatedSpeed_kph'],
                "plate_text" => $response['license_number'],
                "locked" => "1900-01-01 00:00:00",
                "ocr_status" => "10",
                "edited" => "0",
                "user_id" => "0",
                "type" => "Sedan",
                //
                "plate_image_filename" => "",
                "vision" => null,
                "karmen" => null,
                "carmen_old" => null,
                "carmen_general_engine" => null,
                "openalpr" => null,
                "google_v_1" => null,
                "openalpr_response_plate" => null,
                "openalpr_response_car" => null,
                "custom_info" => [],
                "speed_unit" => "km/h" // Or the relevant speed unit
            ];

            $modifiedResponses[] = $modifiedResponse;
        }
        // return $modifiedResponses;
        // return response()->json([$locationID, $m_DateFrom, $m_DateTo]);
        // Your logic to retrieve data
        $tickets = $modifiedResponses;

        // Create the JSON response
        $response = response()->json(['tickets' => $tickets]);

        // You can also set the HTTP status code if needed
        $response->setStatusCode(200); // This is the default status code

        return $response;

    }

    public function ticketDetail(Request $request, $ticketID)
    {
        // return response()->json([$request->all(), $ticketID]);
        $tickets = Analyser::where(['ticket_number' => $ticketID])->first();
        $media = AnalyserMedia::where(['analyser_id' => $tickets['id']]);
        $response = array();
        foreach ($media->get() as $key => $value) {
            $response[] = env('APP_URL') . "/storage/app/unzipMedia/" . $tickets['ticket'] . "/" . $value->filepath_cut;

        }
        // return $response;

        return response()->json(
            [
                "ticket" => [
                    "id" => (int) $tickets['ticket_number'],
                    "location_id" => $tickets['location_id'],
                    "time" => $tickets['capture_at'],
                    "data" => "0d27a09cd22264fda6be76423a0b5b8a03a15d759b47da11c11690090d37cd2c88b6bfbec162c54d7f1470197b1e38f6a789bd68b7808c0d493751a94a786755",
                    "current_speed_limit" => $tickets['speedLimit_kph'],
                    "violating_speed" => $tickets['calculatedSpeed_kph'],
                    "plate_text" => $tickets['license_number'],
                    "locked" => "1900-01-01 00:00:00",
                    "ocr_status" => "10",
                    "user_id" => "0",
                    "modelId" => 24,
                    "hash" => "b81deee1b79c0a2f353f143b87c97dd5",
                    "password" => "3cac7f3f",
                    "validation_status_name" => "verified",
                    "validation_name_color" => "32768",
                    "camera_name" => "Guardian PRO Cameras",
                    "serial" => $tickets['cameraMacAddress'],
                    "plate_image_filename" => $media->first()->filepath_full,
                    "custom_info" => [

                    ],
                    "speed_unit" => "km\/h",
                    "location_name" => Location::where('location_id', $tickets['location_id'])->first()->location_name,
                    "images" => $response,
                    "static_url" => "https:\/\/logixoncloud.com\/source\/tcam\/ticket\/b81deee1b79c0a2f353f143b87c97dd5\/0"
                ]
            ]
        );
    }


    public function ticketStatusUpdate(Request $request, $ticketID, $status)
    {
        // return response()->json([$request->all(), $ticketID, $status]);
        try {
            //code...
            Analyser::where(['ticket_number' => $ticketID])->update(['is_download' => 1]);
            return response()->json(["response" => "SUCCESS"]);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json(["response" => "UNSUCCESS"]);
        }
    }

    public function DownloadMainImage(Request $request, $ticketID)
    {
        $ticket = Analyser::where(['ticket_number' => $ticketID])->first();
        $media = AnalyserMedia::where(['analyser_id' => $ticket['id']])->first();
        $image_url = env('APP_URL') . "/storage/app/unzipMedia/" . $ticket['ticket'] . "/" . $media->filepath_full;

        $filePath = storage_path('app/unzipMedia/' . $ticket['ticket'] . '/' . $media->filepath_full);

        if (!file_exists($filePath)) {
            return response()->json(['error' => 'Image file not found'], 404);
        }

        // Set the appropriate content type for the image
        $contentType = mime_content_type($filePath);

        // Return the image as a downloadable file
        return response()->file($filePath, ['Content-Type' => $contentType]);

        // return response()->json([$request->all(), $ticketID, $ticket, $media, $image_url]);
    }

    public function DownloadBeamImage(Request $request, $hashImg)
    {
        $ticket = Analyser::where(['ticket_number' => $hashImg])->first();
        $mediaForProcess = AnalyserMedia::where('analyser_id', $ticket->id)->first();
        $image_url = env('APP_URL') . "/storage/app/unzipMedia/" . $ticket['ticket'] . "/" . $mediaForProcess->filepath_full;

        // Replace 'image.jpg' with your image file path
        $image = imagecreatefromjpeg($image_url);

        $x1 = null; // x-coordinate of the top-left corner
        $y1 = 800; // y-coordinate of the top-left corner
        $x2 = null; // x-coordinate of the bottom-right corner
        $y2 = 400; // y-coordinate of the bottom-right corner


        $color_default = imagecolorallocate($image, 198, 188, 188);
        $color_green = imagecolorallocate($image, 0, 255, 0);
        $color_text = imagecolorallocate($image, 255, 250, 0); // Color for the text (black)


        // Draw the first rectangle
        // imagerectangle($image, 001, $y1, 200, $y2, $color_1);
        // imagerectangle($image, 200, $y1, 400, $y2, $color_1);
        // imagerectangle($image, 400, $y1, 600, $y2, $color_1);
        // imagerectangle($image, 600, $y1, 800, $y2, $color_1);
        // imagerectangle($image, 800, $y1, 1000, $y2, $color_1);
        // imagerectangle($image, 1000, $y1, 1200, $y2, $color_1);
        // imagerectangle($image, 1200, $y1, 1400, $y2, $color_1);
        // imagerectangle($image, 1400, $y1, 1600, $y2, $color_1);
        // imagerectangle($image, 1600, $y1, 1800, $y2, $color_1);
        // imagerectangle($image, 1800, $y1, 2000, $y2, $color_1);


        $borderThickness = 2; // You can adjust this value to set the desired border thickness

        // Loop to create multiple rectangles forming a thicker border
        // for ($i = 0; $i < $borderThickness; $i++) {
        //     // imagerectangle($image, 0 + $i, $y1 + $i, 2000 - $i, $y2 - $i, $color_1);

        //     imagerectangle($image, 001 + $i, $y1 + $i, 200 - $i, $y2 - $i, $color_default);
        //     imagerectangle($image, 200 + $i, $y1 + $i, 400 - $i, $y2 - $i, $color_default);
        //     imagerectangle($image, 400 + $i, $y1 + $i, 600 - $i, $y2 - $i, $color_default);
        //     imagerectangle($image, 600 + $i, $y1 + $i, 800 - $i, $y2 - $i, $color_default);
        //     imagerectangle($image, 800 + $i, $y1 + $i, 1000 - $i, $y2 - $i, $color_default);
        //     imagerectangle($image, 1000 + $i, $y1 + $i, 1200 - $i, $y2 - $i, $color_green);
        //     imagerectangle($image, 1200 + $i, $y1 + $i, 1400 - $i, $y2 - $i, $color_green);
        //     imagerectangle($image, 1400 + $i, $y1 + $i, 1600 - $i, $y2 - $i, $color_green);
        //     imagerectangle($image, 1600 + $i, $y1 + $i, 1800 - $i, $y2 - $i, $color_green);
        //     imagerectangle($image, 1800 + $i, $y1 + $i, 2000 - $i, $y2 - $i, $color_default);
        // }


        // Loop to create multiple rectangles forming a thicker border
        for ($i = 0; $i < $borderThickness; $i++) {
            for ($j = 0; $j < 16; $j++) {
                $x1 = $j * 120 + $i; // Adjust the rectangle width (2000 / 16 = 125)
                $x2 = $x1 + 120 - $i; // Adjust the ending x-coordinate

                imagerectangle($image, $x1, $y1 + $i, $x2, $y2 - $i, $color_default);
                // if ($j % 2 === 0) {
                // } else {
                //     imagerectangle($image, $x1, $y1 + $i, $x2, $y2 - $i, $color_green);
                // }
                // Add number on top of each rectangle
                $number = "B " . $j + 1; // Increment the number
                $textX = ($x1 + $x2) / 2 - 5; // Adjust text X-coordinate for centering
                $textY = $y1 + $i - 420; // Position text above the rectangle
                imagestring($image, 5, $textX, $textY, $number, $color_text);
            }
        }


        // Output the image to the browser or save it to a file
        header('Content-type: image/jpeg'); // Change the header according to the image type
        imagejpeg($image);

        // Free up memory by destroying the image resource
        imagedestroy($image);
    }
}
