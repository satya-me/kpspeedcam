<?php

namespace App\Jobs;

use App\Models\APISwitch;
use ZipArchive;
use Carbon\Carbon;
use SimpleXMLElement;
use App\Models\Analyser;
use App\Models\Location;
use App\Models\AnalyserMedia;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\File;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class UnzipAndProcessFile implements ShouldQueue
{
    use Dispatchable, Queueable;

    protected $filePath;

    public function __construct($filePath)
    {
        $this->filePath = $filePath;
    }

    public function handle()
    {
        $zipFilePath = $this->filePath; // Assuming the $this->filePath contains the relative path from the 'storage/app/public/' directory


        // debugLog('handle', ['ZIP_LOG_filePath' => storage_path("app/public/" . $zipFilePath)]);

        $zip = new ZipArchive;

        if ($zip->open(storage_path("app/public/" . $zipFilePath)) === TRUE) {
            debugLog('handle', ['ZIP_LOG' => 'Successfully opened the ZIP file']);
            $fullZipPath = storage_path("app/public/" . $zipFilePath);

            // Get details about the uploaded zip file
            $originalName = basename($fullZipPath);
            $originalExtension = pathinfo($originalName, PATHINFO_EXTENSION);
            $size = filesize($fullZipPath);
            $filenameWithoutExtension = explode(".", $originalName);

            // Output or use the obtained details as needed
            debugLog('Zip Details', [
                'Original Name' => $originalName,
                'Extension' => $originalExtension,
                'Size' => $size,
                'filenameWithoutExtension' => $filenameWithoutExtension[0]
            ]);

            $unzipPath = storage_path('app/public/zip/unzipped_files/' . $filenameWithoutExtension[0]); // Define the path to extract the files
            if (!is_dir($unzipPath)) {
                // Create the directory if it doesn't exist
                mkdir($unzipPath, 0777, true);
            }

            $isZip = $zip->extractTo($unzipPath);
            $zip->close();

            if ($isZip) {
                debugLog('handle', ['ZIP_LOG' => 'Successfully unzipped the file']);
                if ($isZip) {
                    $carbon = Carbon::now();
                    $ticket_num = $carbon->timestamp;
                    // Load the XML content from a file or a string
                    $xmlContent = Storage::get("public/zip/unzipped_files/" . $filenameWithoutExtension[0] . "/event.xml"); // Adjust the path accordingly


                    // Parse the XML content
                    $xml = new SimpleXMLElement($xmlContent);

                    $timestamp = (int) $xml->timing->timestamp;
                    $timestamp = $timestamp / 1000;

                    $carbon = Carbon::createFromTimestampMs($timestamp);

                    // Format the Carbon object to a human-readable date and time.
                    $formattedDate = $carbon->toDateTimeString();

                    // Extract and save position data
                    $macId = (String) $xml->cameraMacAddress;
                    $latitude = (float) $xml->position->latitude;
                    $longitude = (float) $xml->position->longitude;
                    $speedLimit_kph = (float) $xml->speed->speedLimit_kph;
                    $speedTrigger_kph = (float) $xml->speed->speedTrigger_kph;
                    $calculatedSpeed_kph = (float) $xml->speed->calculatedSpeed_kph;
                    $targetSpeed_kph = (float) $xml->speed->targetSpeed_kph;

                    $location = Location::where(['mac_id' => $macId])->first();
                    debugLog('LOG', ['Location' => $location]);
                    $location_id = $location->location_id;


                    $ticket = $filenameWithoutExtension[0];

                    // Check if a record with the same ticket already exists
                    $existingRecord = Analyser::where('ticket', $ticket)->first();

                    if (!$existingRecord) {
                        // Create and save the new Analyser object
                        $Analyser = new Analyser;
                        $Analyser->cameraMacAddress = $macId;
                        $Analyser->location_id = $location_id;
                        $Analyser->ticket = $filenameWithoutExtension[0];
                        $Analyser->ticket_number = $ticket_num;
                        $Analyser->speedLimit_kph = $speedLimit_kph;
                        $Analyser->speedTrigger_kph = $speedTrigger_kph;
                        $Analyser->calculatedSpeed_kph = $calculatedSpeed_kph;
                        $Analyser->targetSpeed_kph = $targetSpeed_kph;
                        $Analyser->latitude = $latitude;
                        $Analyser->longitude = $longitude;
                        $Analyser->capture_at = $formattedDate;
                        $Analyser->save();

                        $destinationDirectory = storage_path("app/unzipMedia/" . $filenameWithoutExtension[0]);
                        // debugLog('handle', ['destinationDirectory' => $destinationDirectory]);
                        if (!File::exists($destinationDirectory)) {
                            File::makeDirectory($destinationDirectory, 0755, true, true);
                        }

                        // Extract and save pictures data
                        $pictures = [];
                        foreach ($xml->pictures->image as $image) {
                            $fullFileName = (string) $image->fullFileName;
                            $cutFileName = (string) $image->cutFileName;

                            $AnalyserMedia = new AnalyserMedia;
                            $AnalyserMedia->analyser_id = $Analyser->id;
                            $AnalyserMedia->filepath_full = $fullFileName;
                            $AnalyserMedia->filepath_cut = $cutFileName;
                            $AnalyserMedia->save();


                            // fullFileName
                            $sourceFilePath = storage_path("app/public/zip/unzipped_files/" . $filenameWithoutExtension[0] . "/" . $fullFileName);
                            $destinationDirectory = storage_path("app/unzipMedia/" . $filenameWithoutExtension[0] . "/");
                            // debugLog('handle', ['sourceFilePath' => $sourceFilePath]);
                            // Make sure the destination directory exists; create it if necessary
                            File::ensureDirectoryExists($destinationDirectory);
                            // Copy the file from the source to the destination
                            File::copy($sourceFilePath, $destinationDirectory . $fullFileName);

                            // cutFileName
                            $sourceFilePath = storage_path("app/public/zip/unzipped_files/" . $filenameWithoutExtension[0] . "/" . $cutFileName);
                            $destinationDirectory = storage_path("app/unzipMedia/" . $filenameWithoutExtension[0] . "/");
                            // Make sure the destination directory exists; create it if necessary
                            File::ensureDirectoryExists($destinationDirectory);
                            // Copy the file from the source to the destination
                            File::copy($sourceFilePath, $destinationDirectory . $cutFileName);

                            // Copy the event.xml file
                            $sourceEventXmlPath = storage_path("app/public/zip/unzipped_files/" . $filenameWithoutExtension[0] . "/event.xml");
                            $destinationEventXmlDirectory = storage_path("app/unzipMedia/" . $filenameWithoutExtension[0] . "/");

                            File::ensureDirectoryExists($destinationEventXmlDirectory);
                            File::copy($sourceEventXmlPath, $destinationEventXmlDirectory . "event.xml");

                            $pictures[] = [
                                'fullFileName' => env('APP_URL') . "/storage/app/unzipMedia/" . $filenameWithoutExtension[0] . "/" . $fullFileName,
                                'cutFileName' => env('APP_URL') . "/storage/app/unzipMedia/" . $filenameWithoutExtension[0] . "/" . $cutFileName
                            ];
                        }


                        // CallTheAnalyzer
                        $mediaForProcess = AnalyserMedia::where('analyser_id', $Analyser->id)->first();
                        if (env('APP_ENV') == 'local') {
                            $image_url = 'https://kpspeedcam.com/cut_6308_125.png';
                            // $image_url = env('APP_URL') . "/storage/app/unzipMedia/" . $filenameWithoutExtension[0] . "/" . $mediaForProcess->filepath_cut;
                        } else {
                            $image_url = env('APP_URL') . "/storage/app/unzipMedia/" . $filenameWithoutExtension[0] . "/" . $mediaForProcess->filepath_full;
                        }
                        debugLog('handle', ['image_url' => env('APP_URL') . "/storage/app/unzipMedia/" . $filenameWithoutExtension[0] . "/" . $mediaForProcess->filepath_cut]);

                        $this->CallTheAnalyzer($image_url, $Analyser->id, $filenameWithoutExtension[0]);
                    }



                    // Temp Comment
                    if (!$existingRecord) {
                    }


                    sleep(1);
                    $this->deleteUnzipFile($filenameWithoutExtension[0]);


                    // return response()->json($fileInfo);
                }
                // Perform other necessary operations with the unzipped files
                // Example: Process, read, or manipulate the unzipped files
            } else {
                debugLog('handle', ['ZIP_LOG' => 'Failed to unzip the file']);
                // Handle the case where the file couldn't be unzipped
            }
        } else {
            debugLog('handle', ['ZIP_LOG' => 'Couldn\'t open the ZIP file']);
            // Handle the case where the file couldn't be opened
        }
    }

    public function deleteUnzipFile($folderPath)
    {
        // $folderPath = 'ticket23-09-23_04-36-04_518'; // Specify the folder path you want to delete

        // Check if the folder exists before attempting to delete it
        if (Storage::exists("public/zip/unzipped_files/" . $folderPath)) {
            // Delete the folder and its contents
            Storage::deleteDirectory("public/zip/unzipped_files/" . $folderPath);
            $fileFullPath = storage_path('app/public/zip/' . $folderPath . ".zip");
            debugLog('handle', ['ZPI DELETE' => $fileFullPath]);
            unlink($fileFullPath);
            return "Folder deleted successfully.";
        } else {
            return "Folder does not exist.";
        }
    }

    public function CallTheAnalyzer($imag, $id, $file_name)
    {
        try {
            $FLAG = APISwitch::where("id", 1)->first();
            if ($FLAG->status == "ON") {
                $curl = curl_init();

                curl_setopt_array(
                    $curl,
                    array(
                        CURLOPT_URL => 'https://api.platerecognizer.com/v1/plate-reader',
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => '',
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 0,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => 'POST',
                        CURLOPT_POSTFIELDS => array('upload_url' => $imag),
                        CURLOPT_HTTPHEADER => array(
                            'Authorization: Token ' . env('PLATE_READER_KEY')
                        ),
                    )
                );


                $response = curl_exec($curl);

                curl_close($curl);
                // echo gettype($response);
                // return $response;
                $jsObj = json_decode($response);


                if (isset($jsObj->status_code) == 403) {
                    debugLog('CallTheAnalyzer', ['Error Object' => $jsObj]);
                    return Analyser::where('id', $id)->update(['license_number' => strtoupper("XXXXXXXX"), 'type' => "Unknown"]);
                } else {
                    debugLog('CallTheAnalyzer', ['Success Object' => $jsObj]);

                    return Analyser::where('id', $id)->update(['license_number' => strtoupper($jsObj->results[0]->plate), 'type' => $jsObj->results[0]->vehicle->type]);
                }
            } else {
                debugLog('CallTheAnalyzer', ['Analyzer' => "Analyzer API id Disabled"]);
            }

            // return $jsObj;
        } catch (\Throwable $th) {
            //throw $th;
            debugLog('CallTheAnalyzer', ['catch Error' => $th]);
        }
    }
}
