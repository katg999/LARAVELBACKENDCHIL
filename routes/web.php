<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FinanceDashboardController;
use App\Http\Controllers\ApiDashboardController;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/home', 'HomeController@index')->name('home');
// routes/web.php



Route::get('/api-dashboard', [ApiDashboardController::class, 'index'])->name('api-dashboard');
// New route for fetching data

// Route for the Finance Dashboard
Route::get('/finance-dashboard', [FinanceDashboardController::class, 'index'])->name('finance-dashboard');


