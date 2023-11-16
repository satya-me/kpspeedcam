<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use PhpOffice\PhpSpreadsheet\IOFactory;

class TestController extends Controller
{
    public function showUploadForm()
    {
        return view('test.upload'); // Load the upload form view
    }

    public function upload(Request $request)
    {
        if ($request->hasFile('file')) {
            $file = $request->file('file');

            if ($file->getClientOriginalExtension() === 'xlsx') {
                $path = $file->storeAs('uploads', $file->getClientOriginalName());

                // Load the Excel file
                $spreadsheet = IOFactory::load(storage_path('app/' . $path));
                $sheet = $spreadsheet->getActiveSheet();
                $data = $sheet->toArray();
                array_shift($data);
                // return response()->json([$data]);
                // Database connection
                // Adjust database configuration in the .env file

                foreach ($data as $row) {
                    DB::table('locations')->insert([
                        'location_id' => $row[0],
                        'location_name' => $row[1],
                        'mac_id' => $row[2],
                        // Adjust column names based on your database structure
                    ]);
                }

                return "Data from Excel file inserted into the database successfully.";
            } else {
                return "Please upload an Excel file in .xlsx format.";
            }
        } else {
            return "Error uploading the file.";
        }
    }

    public function beam(Request $request)
    {
        // Replace 'image.jpg' with your image file path
        $image = imagecreatefromjpeg('https://kpspeedcam.com/app/storage/app/unzipMedia/ticket23-09-23_11-52-49_900/full_9765_98.jpg');

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

    public function beam2(Request $request)
    {

    }

}
