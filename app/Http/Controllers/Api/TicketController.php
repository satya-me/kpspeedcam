<?php

namespace App\Http\Controllers\Api;

use App\Models\Analyser;
use App\Models\Location;
use Illuminate\Http\Request;
use App\Models\AnalyserMedia;
use App\Http\Controllers\Controller;
use Intervention\Image\Facades\Image;

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
        // $image_url = env('APP_URL') . "/storage/app/unzipMedia/" . $ticket['ticket'] . "/" . $mediaForProcess->filepath_full;

        // Path to your XML file or XML data as a string
        $xmlString = file_get_contents(env('APP_URL') . '/storage/app/unzipMedia/' . $ticket->ticket . '/event.xml');
        $xml = simplexml_load_string($xmlString);

        $imgs = [];
        $frameCount = 1;
        $fontUrl = 'https://fonts.googleapis.com/css2?family=Open+Sans&display=swap'; // Use the Google Fonts URL here


        foreach ($xml->pictures->children() as $mark) {
            $_FrameCount = $frameCount++;
            // echo "<pre>";
            // print_r($mark);
            // exit;
            // Path to your image
            $inputImage = env('APP_URL') . '/storage/app/unzipMedia/' . $ticket->ticket . '/' . $mark->fullFileName;

            // Load the image
            $img = Image::make($inputImage);

            // Iterate through the frameZones in the XML
            $rectanglesCount = 1;
            foreach ($xml->geometry->frameZones->children() as $beam) {
                $x = (int) $beam->posX_px;
                $y = (int) $beam->posY_px;
                $width = (int) $beam->width_px;
                $height = (int) $beam->height_px;

                // Draw a rectangle on the image
                $color = '#ffffff'; // Default color

                // Change color for rectangles 4 to 7
                if ($rectanglesCount >= $mark->zoneBeamFirst && $rectanglesCount <= $mark->zoneBeamLast) {
                    $rectangleColor = '#06e916'; // Change color to green (#06e916) for rectangles 4 to 7
                    $fontColor = '#06e916'; // Change font color for rectangles 4 to 7
                    $fontSize = 30;
                } else {
                    $rectangleColor = '#ffffff'; // Default color
                    $fontColor = '#feffa8'; // Default font color
                    $fontSize = 25;
                }

                $fontPath = storage_path("app/public/open-sans/OpenSans-Regular.ttf");

                // Draw a rectangle on the image
                $img->rectangle($x, $y, $x + $width, $y + $height, function ($draw) use ($rectangleColor) {
                    $draw->border(1, $rectangleColor); // Set the color for the rectangle
                });

                // Place a count number on top of the rectangle
                for ($i = 1; $i <= 2; $i++) {
                    for ($j = 1; $j <= 2; $j++) {
                        $img->text($rectanglesCount, $x + ($width / 2) + $i, $y + ($height - 25) + $j, function ($font) use ($fontColor, $fontSize, $fontPath) {
                            $font->file($fontPath); // Font file if required
                            $font->size($fontSize); // Adjust the font size as needed (e.g., 20)
                            $font->color('#000000'); // Set the font color based on condition
                            $font->align('center');
                            $font->valign('middle');
                        });
                    }
                }

                $img->text($rectanglesCount, $x + ($width / 2), $y + ($height - 25), function ($font) use ($fontColor, $fontSize, $fontPath) {
                    $font->file($fontPath); // Font file if required
                    $font->size($fontSize); // Adjust the font size as needed (e.g., 20)
                    $font->color($fontColor); // Set the font color based on condition
                    $font->align('center');
                    $font->valign('middle');
                });

                $rectanglesCount++;
            }

            // image information
            $img->text($ticket->capture_at, 10, 75, function ($font) use ($fontPath) {
                $font->file($fontPath);
                $font->size(50);
                $font->color('#feffa8');
            });

            $img->text("Speed: " . round($ticket->calculatedSpeed_kph) . " km/h", 10, 150, function ($font) use ($fontPath) {
                $font->file($fontPath);
                $font->size(50);
                $font->color('#feffa8');
            });

            $img->text("Frame: " . $_FrameCount . '/' . '3', 10, 225, function ($font) use ($fontPath) {
                $font->file($fontPath);
                $font->size(50);
                $font->color('#feffa8');
            });

            $img->text("Latitude: " . $ticket->latitude, 1150, 75, function ($font) use ($fontPath) {
                $font->file($fontPath);
                $font->size(50);
                $font->color('#feffa8');
            });

            $img->text("Longitude: " . $ticket->longitude, 1150, 150, function ($font) use ($fontPath) {
                $font->file($fontPath);
                $font->size(50);
                $font->color('#feffa8');
            });

            $location = Location::where(['location_id' => $ticket->location_id])->first();
            $lName = $location ? $location->location_name : 'Error!';
            $img->text("Location: " . $lName, 10, 900, function ($font) use ($fontPath) {
                $font->file($fontPath);
                $font->size(50);
                $font->color('#feffa8');
            });

            for ($i = 1; $i <= 2; $i++) {
                for ($j = 1; $j <= 2; $j++) {
                    $img->text("Prosecuted vehicle within Green Beam ", 10 + $i, 975 + $j, function ($font) use ($fontPath) {
                        $font->file(storage_path("app/public/open-sans/OpenSans-Regular.ttf"));
                        $font->size(40);
                        $font->color('#000000');
                    });
                }
            }

            $img->text("Prosecuted vehicle within Green Beam ", 10, 975, function ($font) use ($fontPath) {
                $font->file(storage_path("app/public/open-sans/OpenSans-Regular.ttf"));
                $font->size(40);
                $font->color('#feffa8');
            });



            // Save the modified image
            // storage_path("app/unzipMedia/" . 'ticket23-10-25_12-43-33_900' . "/".$mark->fullFileName);
            $destinationPath = storage_path("app/unzipMedia/" . $ticket->ticket . "/beam/");

            // Ensure the destination directory exists, create it if it doesn't
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0777, true);
            }

            // Verify the image object is valid and save it to the destination path
            if ($img && $mark && $mark->fullFileName) {
                $img->save($destinationPath . $_FrameCount . '_' . $mark->fullFileName);
            }
            // $img->save(storage_path("app/unzipMedia/" . 'ticket23-09-23_11-52-49_900' . "/beam/" . $mark->fullFileName));

            // Return the modified image or do further processing as needed
            $image_url = env('APP_URL') . "/storage/app/unzipMedia/" . $ticket->ticket . "/beam/" . $_FrameCount . '_' . $mark->fullFileName;
            // return $img->response();
            $imgs[] = $image_url;
        }
        return response()->json([
            'beam_images' => $imgs
        ]);


    }

    public function IMG()
    {
        // return 5;
        // Path to your XML file or XML data as a string
        $xmlString = file_get_contents('https://kpspeedcam.com/app/storage/app/unzipMedia/ticket23-10-25_12-43-33_980/event.xml');
        $xml = simplexml_load_string($xmlString);

        $imgs = [];
        foreach ($xml->pictures->children() as $mark) {

            // echo "<pre>";
            // print_r($mark);
            // exit;
            // Path to your image
            $inputImage = 'https://kpspeedcam.com/app/storage/app/unzipMedia/ticket23-10-25_12-43-33_980/full_4135_39808.jpg';

            // Load the image
            $img = Image::make($inputImage);

            // Iterate through the frameZones in the XML
            $rectanglesCount = 0;
            foreach ($xml->geometry->frameZones->children() as $beam) {
                $x = (int) $beam->posX_px;
                $y = (int) $beam->posY_px;
                $width = (int) $beam->width_px;
                $height = (int) $beam->height_px;

                // Draw a rectangle on the image
                $color = '#ffffff'; // Default color

                // Change color for rectangles 4 to 7
                if ($rectanglesCount >= $mark->zoneBeamFirst && $rectanglesCount <= $mark->zoneBeamLast) {
                    $color = '#06e916'; // Change color to red (#06e916) for rectangles 4 to 7
                }

                $img->rectangle($x, $y, $x + $width, $y + $height, function ($draw) use ($color) {
                    $draw->border(1, $color); // Set the color for the rectangle
                });

                // Place a count number on top of the rectangle
                $rectanglesCount++;
                $img->text($rectanglesCount, $x + ($width / 2), $y + ($height / 2), function ($font) {
                    $font->file(public_path('open-sans\OpenSans-Regular.ttf')); // Font file if required
                    $font->size(20); // Adjust the font size as needed (e.g., 48)
                    $font->color('#feffa8'); // Font color
                    $font->align('center');
                    $font->valign('middle');
                });



                // image information
                $img->text('The quick brown fox jumps over the lazy dog.', 120, 100, function ($font) {
                    $font->color(array(255, 255, 255, 0.5));
                    $font->file(public_path('open-sans\OpenSans-Regular.ttf'));
                    $font->size(35);
                });
            }

            // Save the modified image
            // storage_path("app/unzipMedia/" . 'ticket23-10-25_12-43-33_900' . "/".$mark->fullFileName);
            $destinationPath = storage_path("app/unzipMedia/ticket23-09-23_11-52-49_900/beam/");

            // Ensure the destination directory exists, create it if it doesn't
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0777, true);
            }

            // Verify the image object is valid and save it to the destination path
            if ($img && $mark && $mark->fullFileName) {
                $img->save($destinationPath . $mark->fullFileName);
            }
            // $img->save(storage_path("app/unzipMedia/" . 'ticket23-09-23_11-52-49_900' . "/beam/" . $mark->fullFileName));

            // Return the modified image or do further processing as needed
            $image_url = env('APP_URL') . "/storage/app/unzipMedia/" . "ticket23-09-23_11-52-49_900" . "/beam/" . $mark->fullFileName;
            return $img->response();
        }
    }

}
