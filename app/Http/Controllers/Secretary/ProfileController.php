<?php
namespace App\Http\Controllers\Secretary;

use App\Http\Controllers\Controller;

class ProfileController extends Controller
{
    public function show()
    {
        return view('secretary.profile.show'); // create this blade or reuse an existing view
    }
}