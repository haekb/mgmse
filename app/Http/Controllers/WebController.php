<?php


namespace App\Http\Controllers;

use App\Models\Server;
use Illuminate\Http\Request;

class WebController extends Controller
{
    public function index()
    {
        return view('welcome');
    }

    public function privacy()
    {
        return view('privacy');
    }
}
