<?php

namespace App\Http\Controllers;

use App\Models\Analyser;
use App\Models\APISwitch;
use App\Models\Location;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\TicketController;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        return view('home');
    }


    public function Challan(Request $request)
    {
        $BEAM = new TicketController;
        $AnalyserLatest = Analyser::latest()->first();
        $BEAM->DownloadBeamImage($request, $AnalyserLatest->ticket_number);
        $LatestThree = Analyser::latest()->take(3)->get();
        return view('createchallan', compact('AnalyserLatest', 'LatestThree'));
    }
    public function Report()
    {
        $Analyser = Analyser::orderBy('created_at', 'desc')->paginate(10);
        return view('report', compact('Analyser'));
    }

    public function apiOnOff(Request $request)
    {

        // return $request->status;
        APISwitch::where('id', 1)->update(['status' => $request->status]);
        return back()->with('success', '');
    }

    public function Settings()
    {
        $location = Location::get();
        return view('settings', compact('location'));
    }

    public function UpdateLocation(Request $request)
    {
        // return $request;
        return Location::where(['location_id' => $request->location_id])->update([
            'location_name' => $request->location_name,
            'mac_id' => $request->mac_id,
        ]);
    }

    public function updateKey(Request $request)
    {
        $request->validate([
            'plate_reader_key' => 'required',
        ]);

        $newKey = $request->input('plate_reader_key');

        // Update .env file
        file_put_contents(
            base_path('.env'),
            str_replace(
                'PLATE_READER_KEY=' . env('PLATE_READER_KEY'),
                'PLATE_READER_KEY=' . $newKey,
                file_get_contents(base_path('.env'))
            )
        );

        // Clear config cache
        // Artisan::call('config:clear');

        return redirect()->back()->with('message', 'Key updated successfully');
    }
}
