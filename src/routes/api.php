<?php

use Illuminate\Support\Facades\Route;
use App\Models\Permission;
use App\Models\CategoryPermission;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('v5k4pgi3-login', 'LoginController@login');
Route::post('logout', 'LoginController@logout');

Route::get('schedule-status/list', 'ScheduleStatusController@list');
Route::get('reservation-status/list', 'ReservationStatusController@list');
Route::get('reservation-organization/list', 'ReservationOrganizationController@list');
Route::get('reservation-organization/get/{id}', 'ReservationOrganizationController@get');
Route::get('schedule-place/list', 'SchedulePlaceController@list');
Route::get('schedule-place/get/{id}', 'SchedulePlaceController@get');
Route::get('schedule-usage/list', 'ScheduleUsageController@list');
Route::get('schedule-usage/get/{id}', 'ScheduleUsageController@get');
Route::get('schedule-timetable/list', 'ScheduleTimetableController@list');
Route::get('schedule-timetable/get/{id}', 'ScheduleTimetableController@get');

Route::get('schedule/list/public', 'ScheduleController@listPublic');

Route::middleware(sprintf('category:%s', CategoryPermission::READ))->group(function () {
    Route::get('setting/get/{category}/{key}', 'SettingController@get');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::middleware(sprintf('category:%s', CategoryPermission::WRITE))->group(function () {
        Route::post('setting/update/{category}/{key}', 'SettingController@update');
    });

    Route::middleware(sprintf('can:%s', Permission::RESERVATION_READ))->group(function () {
        Route::get('month/list', 'MonthController@list');
        Route::get('month/get/{id}', 'MonthController@get');
        Route::get('schedule/list', 'ScheduleController@list');
        Route::get('schedule/get/{id}', 'ScheduleController@get');
        Route::get('reservation/list', 'ReservationController@list');
        Route::get('reservation/list-for-schedule/{scheduleId}', 'ReservationController@listForSchedule');
        Route::get('reservation/get/{id}', 'ReservationController@get');

        Route::middleware(sprintf('can:%s', Permission::RESERVATION_WRITE))->group(function () {
            Route::post('reservation-organization/add', 'ReservationOrganizationController@add');
            Route::post('reservation-organization/update/{id}', 'ReservationOrganizationController@update');
            Route::post('reservation-organization/reorder', 'ReservationOrganizationController@reorder');
            Route::post('schedule-place/add', 'SchedulePlaceController@add');
            Route::post('schedule-place/update/{id}', 'SchedulePlaceController@update');
            Route::post('schedule-place/reorder', 'SchedulePlaceController@reorder');
            Route::post('schedule-usage/add', 'ScheduleUsageController@add');
            Route::post('schedule-usage/update/{id}', 'ScheduleUsageController@update');
            Route::post('schedule-usage/reorder', 'ScheduleUsageController@reorder');
            Route::post('schedule-timetable/add', 'ScheduleTimetableController@add');
            Route::post('schedule-timetable/update/{id}', 'ScheduleTimetableController@update');
            Route::post('schedule-timetable/reorder', 'ScheduleTimetableController@reorder');

            Route::get('month/send/prepare/{id}', 'MonthController@prepareSendInfo');
            Route::post('month/send/{id}', 'MonthController@send');
            Route::post('schedule/add-list', 'ScheduleController@addList');
            Route::post('schedule/update/{id}', 'ScheduleController@update');
            Route::post('schedule/bulk-change', 'ScheduleController@bulkChange');
            Route::post('reservation/split/{id}', 'ReservationController@split');
            Route::post('reservation/bulk-change', 'ReservationController@bulkChange');
        });
    });
});
