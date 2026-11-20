<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\LoginPageController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\CustomerPortalController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EnquiryController;
use App\Http\Controllers\FeeScheduleController;
use App\Http\Controllers\FollowUpController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\IndustryUnitController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\PortalEntryController;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UserDirectoryController;
use App\Http\Controllers\WorkOrderController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::post('/locale', [LocaleController::class, 'update'])->name('locale.update');

Route::get('/admin', [PortalEntryController::class, 'admin'])->name('admin.entry');
Route::get('/staff', [PortalEntryController::class, 'staff'])->name('staff.entry');
Route::redirect('/customer', '/customer/login');

$officeClients = function (): void {
    Route::middleware('permission:clients.manage')->group(function () {
        Route::get('clients/create', [ClientController::class, 'create'])->name('clients.create');
        Route::post('clients', [ClientController::class, 'store'])->name('clients.store');
        Route::get('clients/{client}/edit', [ClientController::class, 'edit'])->name('clients.edit');
        Route::put('clients/{client}', [ClientController::class, 'update'])->name('clients.update');
        Route::post('clients/{client}/status', [ClientController::class, 'status'])->name('clients.status');
        Route::get('clients/{client}/units/create', [IndustryUnitController::class, 'create'])->name('clients.units.create');
        Route::post('clients/{client}/units', [IndustryUnitController::class, 'store'])->name('clients.units.store');
        Route::get('clients/{client}/units/{unit}/edit', [IndustryUnitController::class, 'edit'])->name('clients.units.edit');
        Route::put('clients/{client}/units/{unit}', [IndustryUnitController::class, 'update'])->name('clients.units.update');
        Route::post('clients/{client}/units/{unit}/status', [IndustryUnitController::class, 'status'])->name('clients.units.status');
        Route::delete('clients', [ClientController::class, 'destroyMany'])->name('clients.destroy-many');
        Route::delete('clients/{client}', [ClientController::class, 'destroy'])->name('clients.destroy');
    });

    Route::middleware('permission:clients.view')->group(function () {
        Route::get('clients', [ClientController::class, 'index'])->name('clients.index');
        Route::get('clients/{client}/units/{unit}', [IndustryUnitController::class, 'show'])->name('clients.units.show');
        Route::get('clients/{client}', [ClientController::class, 'show'])->name('clients.show');
    });
};

$officeCatalog = function (): void {
    Route::middleware('permission:services.manage')->group(function () {
        Route::get('services/create', [ServiceController::class, 'create'])->name('services.create');
        Route::post('services', [ServiceController::class, 'store'])->name('services.store');
        Route::get('services/{service}/edit', [ServiceController::class, 'edit'])->name('services.edit');
        Route::put('services/{service}', [ServiceController::class, 'update'])->name('services.update');
        Route::post('services/{service}/status', [ServiceController::class, 'status'])->name('services.status');
    });

    Route::middleware('permission:services.view')->group(function () {
        Route::get('services', [ServiceController::class, 'index'])->name('services.index');
        Route::get('services/{service}', [ServiceController::class, 'show'])->name('services.show');
    });

    Route::middleware('permission:fees.manage')->group(function () {
        Route::get('fees/create', [FeeScheduleController::class, 'create'])->name('fees.create');
        Route::post('fees', [FeeScheduleController::class, 'store'])->name('fees.store');
        Route::get('fees/{feeSchedule}/edit', [FeeScheduleController::class, 'edit'])->name('fees.edit');
        Route::put('fees/{feeSchedule}', [FeeScheduleController::class, 'update'])->name('fees.update');
        Route::post('fees/{feeSchedule}/status', [FeeScheduleController::class, 'status'])->name('fees.status');
    });

    Route::middleware('permission:fees.view')->group(function () {
        Route::get('fees', [FeeScheduleController::class, 'index'])->name('fees.index');
        Route::get('fees/{feeSchedule}', [FeeScheduleController::class, 'show'])->name('fees.show');
    });
};

$officeQuotations = function (): void {
    Route::middleware('permission:quotations.manage')->group(function () {
        Route::get('quotations/create', [QuotationController::class, 'create'])->name('quotations.create');
        Route::post('quotations', [QuotationController::class, 'store'])->name('quotations.store');
        Route::get('quotations/{quotation}/revise', [QuotationController::class, 'revise'])->name('quotations.revise');
        Route::post('quotations/{quotation}/revisions', [QuotationController::class, 'storeRevision'])->name('quotations.revisions.store');
        Route::post('quotations/{quotation}/status', [QuotationController::class, 'status'])->name('quotations.status');
    });

    Route::middleware('permission:quotations.view')->group(function () {
        Route::get('quotations', [QuotationController::class, 'index'])->name('quotations.index');
        Route::get('quotations/{quotation}/compare', [QuotationController::class, 'compare'])->name('quotations.compare');
        Route::get('quotations/{quotation}/revisions/{revision}', [QuotationController::class, 'showRevision'])->name('quotations.revisions.show');
        Route::get('quotations/{quotation}/revisions/{revision}/pdf', [QuotationController::class, 'pdf'])->name('quotations.revisions.pdf');
        Route::get('quotations/{quotation}', [QuotationController::class, 'show'])->name('quotations.show');
    });
};

$officeFollowUps = function (): void {
    Route::middleware('permission:follow-ups.view')->group(function () {
        Route::get('follow-ups', [FollowUpController::class, 'index'])->name('follow-ups.index');
    });

    Route::middleware('permission:follow-ups.create')->group(function () {
        Route::post('follow-ups', [FollowUpController::class, 'store'])->name('follow-ups.store');
    });

    Route::middleware('permission:follow-ups.edit')->group(function () {
        Route::put('follow-ups/{followUp}', [FollowUpController::class, 'update'])->name('follow-ups.update');
    });

    Route::middleware('permission:follow-ups.complete')->group(function () {
        Route::post('follow-ups/{followUp}/status', [FollowUpController::class, 'status'])->name('follow-ups.status');
        Route::post('follow-ups/{followUp}/complete', [FollowUpController::class, 'complete'])->name('follow-ups.complete');
    });

    Route::middleware('permission:follow-ups.delete')->group(function () {
        Route::delete('follow-ups', [FollowUpController::class, 'destroyMany'])->name('follow-ups.destroy-many');
        Route::delete('follow-ups/{followUp}', [FollowUpController::class, 'destroy'])->name('follow-ups.destroy');
    });
};

$officeWork = function (): void {
    Route::middleware('permission:enquiries.manage')->group(function () {
        Route::get('enquiries/create', [EnquiryController::class, 'create'])->name('enquiries.create');
        Route::post('enquiries', [EnquiryController::class, 'store'])->name('enquiries.store');
        Route::get('enquiries/{enquiry}/edit', [EnquiryController::class, 'edit'])->name('enquiries.edit');
        Route::put('enquiries/{enquiry}', [EnquiryController::class, 'update'])->name('enquiries.update');
        Route::post('enquiries/{enquiry}/status', [EnquiryController::class, 'status'])->name('enquiries.status');
        Route::post('enquiries/{enquiry}/assignment', [EnquiryController::class, 'assign'])->name('enquiries.assign');
        Route::post('enquiries/{enquiry}/follow-ups', [EnquiryController::class, 'followUp'])->name('enquiries.follow-ups.store');
        Route::delete('enquiries', [EnquiryController::class, 'destroyMany'])->name('enquiries.destroy-many');
        Route::delete('enquiries/{enquiry}', [EnquiryController::class, 'destroy'])->name('enquiries.destroy');
    });

    Route::middleware('permission:enquiries.view')->group(function () {
        Route::get('enquiries', [EnquiryController::class, 'index'])->name('enquiries.index');
        Route::get('enquiries/{enquiry}', [EnquiryController::class, 'show'])->name('enquiries.show');
    });

    Route::middleware('permission:work-orders.manage')->group(function () {
        Route::post('enquiries/{enquiry}/convert', [EnquiryController::class, 'convert'])->name('enquiries.convert');
        Route::get('work-orders/{workOrder}/edit', [WorkOrderController::class, 'edit'])->name('work-orders.edit');
        Route::put('work-orders/{workOrder}', [WorkOrderController::class, 'update'])->name('work-orders.update');
        Route::post('work-orders/{workOrder}/status', [WorkOrderController::class, 'status'])->name('work-orders.status');
        Route::post('work-orders/{workOrder}/assignment', [WorkOrderController::class, 'assign'])->name('work-orders.assign');
    });

    Route::middleware('permission:work-orders.view')->group(function () {
        Route::get('work-orders', [WorkOrderController::class, 'index'])->name('work-orders.index');
        Route::get('work-orders/{workOrder}', [WorkOrderController::class, 'show'])->name('work-orders.show');
    });
};

Route::prefix('admin')->name('admin.')->group(function () use ($officeClients, $officeCatalog, $officeWork, $officeQuotations, $officeFollowUps) {
    Route::middleware('guest:admin')->group(function () {
        Route::get('login', [LoginPageController::class, 'admin'])->name('login');
    });

    Route::middleware(['auth:admin', 'portal:admin'])->group(function () use ($officeClients, $officeCatalog, $officeWork, $officeQuotations, $officeFollowUps) {
        Route::post('logout', LogoutController::class)->name('logout');
        Route::get('dashboard', DashboardController::class)->name('dashboard');
        Route::get('search', [SearchController::class, 'index'])->name('search');
        Route::get('users', [UserDirectoryController::class, 'index'])->middleware('permission:users.view')->name('users.index');
        Route::get('settings', [SettingsController::class, 'index'])->middleware('permission:settings.view')->name('settings.index');
        Route::get('settings/audit-logs', [AuditLogController::class, 'index'])->middleware('permission:audit.view')->name('settings.audit-logs');
        $officeClients();
        $officeCatalog();
        $officeWork();
        $officeFollowUps();
        $officeQuotations();
    });
});

Route::prefix('staff')->name('staff.')->group(function () use ($officeClients, $officeCatalog, $officeWork, $officeQuotations, $officeFollowUps) {
    Route::middleware('guest:staff')->group(function () {
        Route::get('login', [LoginPageController::class, 'staff'])->name('login');
    });

    Route::middleware(['auth:staff', 'portal:staff'])->group(function () use ($officeClients, $officeCatalog, $officeWork, $officeQuotations, $officeFollowUps) {
        Route::post('logout', LogoutController::class)->name('logout');
        Route::get('dashboard', DashboardController::class)->name('dashboard');
        Route::get('search', [SearchController::class, 'index'])->name('search');
        $officeClients();
        $officeCatalog();
        $officeWork();
        $officeFollowUps();
        $officeQuotations();
    });
});

Route::prefix('customer')->name('customer.')->group(function () {
    Route::get('login', [CustomerPortalController::class, 'show'])->name('login');
    Route::get('dashboard', [CustomerPortalController::class, 'show'])->name('dashboard');
});
