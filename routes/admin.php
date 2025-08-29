<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\KycController;
use App\Http\Controllers\Admin\UTMController;
use App\Http\Controllers\Admin\LeadController;
use App\Http\Controllers\Admin\LoanController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SystemController;
use App\Http\Controllers\Admin\DepositController;
use App\Http\Controllers\Admin\LoanUtrController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DecisionController;
use App\Http\Controllers\Admin\FrontendController;
use App\Http\Controllers\Admin\LanguageController;
use App\Http\Controllers\Admin\LoanPlanController;
use App\Http\Controllers\Admin\ExtensionController;
use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\CollectionController;
use App\Http\Controllers\Admin\SubscriberController;
use App\Http\Controllers\Admin\WithdrawalController;
use App\Http\Controllers\Admin\ManageUsersController;
use App\Http\Controllers\Admin\LoanApprovalController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\LoanDisbursalController;
use App\Http\Controllers\Admin\ManualGatewayController;
use App\Http\Controllers\Admin\SupportTicketController;
use App\Http\Controllers\Admin\GeneralSettingController;
use App\Http\Controllers\Admin\WithdrawMethodController;
use App\Http\Controllers\Admin\AutomaticGatewayController;
use App\Http\Controllers\Admin\CreditAssessmentController;
use App\Http\Controllers\Admin\CronConfigurationController;
use App\Http\Controllers\Admin\Auth\ResetPasswordController;
use App\Http\Controllers\Admin\Auth\ForgotPasswordController;
use App\Http\Controllers\Admin\CreditBureauController;
use App\Http\Controllers\Admin\ExperianCreditBureauController;
use App\Http\Controllers\Api\ScoreMeWebhookController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\EventLogController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\OSReportController;
use App\Http\Controllers\Admin\DigitapController;

Route::controller(LoginController::class)->group(function () {
    Route::get('/', 'showLoginForm')->name('login');
    Route::post('/', 'login')->name('login');
    Route::get('logout', 'logout')->middleware('admin')->name('logout');
});

// Admin Password Reset
Route::prefix('password')->name('password.')->group(function () {
    Route::controller(ForgotPasswordController::class)->group(function () {
        Route::get('reset', 'showLinkRequestForm')->name('reset');
        Route::post('reset', 'sendResetCodeEmail');
        Route::get('code-verify', 'codeVerify')->name('code.verify');
        Route::post('verify-code', 'verifyCode')->name('verify.code');
    });

    Route::controller(ResetPasswordController::class)->group(function () {
        Route::get('reset/{token}', 'showResetForm')->name('reset.form');
        Route::post('reset/change', 'reset')->name('change');
    });
});

Route::middleware('admin')->group(function () {
    Route::controller(AdminController::class)->group(function () {
        Route::get('dashboard', 'dashboard')->name('dashboard');
        Route::get('profile', 'profile')->name('profile');
        Route::post('profile', 'profileUpdate')->name('profile.update');
        Route::get('password', 'password')->name('password');
        Route::post('password', 'passwordUpdate')->name('password.update');
    
        // Notification
        Route::get('notifications', 'notifications')->name('notifications');
        Route::get('notification/read/{id}', 'notificationRead')->name('notification.read');
        Route::get('notifications/read-all', 'readAll')->name('notifications.readAll');
    
        Route::get('download-attachments/{file_hash}', 'downloadAttachment')->name('download.attachment');
    });

    Route::get('/secure-file/{filename}', function ($filename) {
        $path = config('services.docs.upload_kfs_doc') . '/' . $filename; 

        if (!file_exists($path)) {
            abort(404); 
        }
    
        $mimeType = mime_content_type($path); 
    
        return response()->file($path, ['Content-Type' => $mimeType]);
    });

    Route::get('/secure-document/{filename}', function ($filename) {
        $filePath = config('services.docs.upload_kfs_doc') . '/' . $filename;
    
        if (!file_exists($filePath)) {
            abort(404, 'File not found');
        }
    
        return response()->file($filePath);
    })->where('filename', '.*');

    Route::get('/kfs-document/{filename}/{loanon}', function ($filename, $loanon) {
        $filePath = config('services.docs.upload_kfs_doc') . '/documents/loan_'. $loanon . '/kfs/updated_' . $filename;
    
        if (!file_exists($filePath)) {
            abort(404, 'File not found');
        }
    
        return response()->file($filePath);
    })->where('filename', '.*');

    // Users Manager
    Route::controller(ManageUsersController::class)
    ->name('users.')
    ->prefix('users')
    ->group(function () {
        Route::get('/', 'allUsers')->name('all');
        Route::get('active', 'activeUsers')->name('active');
        Route::get('banned', 'bannedUsers')->name('banned');
        Route::get('email-verified', 'emailVerifiedUsers')->name('email.verified');
        Route::get('email-unverified', 'emailUnverifiedUsers')->name('email.unverified');
        Route::get('mobile-unverified', 'mobileUnverifiedUsers')->name('mobile.unverified');
        Route::get('kyc-unverified', 'kycUnverifiedUsers')->name('kyc.unverified');
        Route::get('kyc-pending', 'kycPendingUsers')->name('kyc.pending');
        Route::get('mobile-verified', 'mobileVerifiedUsers')->name('mobile.verified');
        Route::get('with-balance', 'usersWithBalance')->name('with.balance');

        Route::get('detail/{id}', 'detail')->name('detail');
        Route::get('kyc-data/{id}', 'kycDetails')->name('kyc.details');
        Route::get('bank-data/{id}', 'bankDetails')->name('bank.details');
        Route::get('device-data/{id}', 'deviceDetails')->name('device.data');
        Route::get('contacts/{id}', 'showContacts')->name('contact.details');
        Route::post('kyc-approve/{id}', 'kycApprove')->name('kyc.approve');
        Route::post('kyc-reject/{id}', 'kycReject')->name('kyc.reject');
        Route::post('update/{id}', 'update')->name('update');
        Route::post('add-sub-balance/{id}', 'addSubBalance')->name('add.sub.balance');
        Route::get('send-notification/{id}', 'showNotificationSingleForm')->name('notification.single');
        Route::post('send-notification/{id}', 'sendNotificationSingle')->name('notification.single');
        Route::get('login/{id}', 'login')->name('login');
        Route::post('status/{id}', 'status')->name('status');

        Route::get('send-notification', 'showNotificationAllForm')->name('notification.all');
        Route::post('send-notification', 'sendNotificationAll')->name('notification.all.send');
        Route::get('list', 'list')->name('list');
        Route::get('notification-log/{id}', 'notificationLog')->name('notification.log');
    });

    // Subscriber
    Route::controller(SubscriberController::class)
    ->prefix('subscriber')
    ->name('subscriber.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('send-email', 'sendEmailForm')->name('send.email');
        Route::post('remove/{id}', 'remove')->name('remove');
        Route::post('send-email', 'sendEmail')->name('send.email');
    });


    // Deposit Gateway
    Route::name('gateway.')
    ->prefix('gateway')
    ->group(function () {

        // Automatic Gateway
        Route::controller(AutomaticGatewayController::class)
            ->prefix('automatic')
            ->name('automatic.')
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('edit/{alias}', 'edit')->name('edit');
                Route::post('update/{code}', 'update')->name('update');
                Route::post('remove/{id}', 'remove')->name('remove');
                Route::post('status/{id}', 'status')->name('status');
            });

        // Manual Methods
        Route::controller(ManualGatewayController::class)
            ->prefix('manual')
            ->name('manual.')
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('new', 'create')->name('create');
                Route::post('new', 'store')->name('store');
                Route::get('edit/{alias}', 'edit')->name('edit');
                Route::post('update/{id}', 'update')->name('update');
                Route::post('status/{id}', 'status')->name('status');
            });
    });


    // DEPOSIT SYSTEM
    Route::controller(DepositController::class)
    ->prefix('deposit')
    ->name('deposit.')
    ->group(function () {
        Route::get('/', 'deposit')->name('list');
        Route::get('pending', 'pending')->name('pending');
        Route::get('rejected', 'rejected')->name('rejected');
        Route::get('approved', 'approved')->name('approved');
        Route::get('successful', 'successful')->name('successful');
        Route::get('initiated', 'initiated')->name('initiated');
        Route::get('details/{id}', 'details')->name('details');
        Route::post('reject', 'reject')->name('reject');
        Route::post('approve/{id}', 'approve')->name('approve');
    });


    // WITHDRAW SYSTEM
    Route::name('withdraw.')
    ->prefix('withdraw')
    ->group(function () {
        Route::controller(WithdrawalController::class)->group(function () {
            Route::get('pending', 'pending')->name('pending');
            Route::get('approved', 'approved')->name('approved');
            Route::get('rejected', 'rejected')->name('rejected');
            Route::get('log', 'log')->name('log');
            Route::get('details/{id}', 'details')->name('details');
            Route::post('approve', 'approve')->name('approve');
            Route::post('reject', 'reject')->name('reject');
        });

        // Withdraw Method
        Route::controller(WithdrawMethodController::class)
            ->prefix('method')
            ->name('method.')
            ->group(function () {
                Route::get('/', 'methods')->name('index');
                Route::get('create', 'create')->name('create');
                Route::post('create', 'store')->name('store');
                Route::get('edit/{id}', 'edit')->name('edit');
                Route::post('edit/{id}', 'update')->name('update');
                Route::post('status/{id}', 'status')->name('status');
            });
    });


    // Report
    Route::controller(ReportController::class)
    ->prefix('report')
    ->name('report.')
    ->group(function () {
        Route::get('transaction', 'transaction')->name('transaction');
        Route::get('login/history', 'loginHistory')->name('login.history');
        Route::get('login/ipHistory/{ip}', 'loginIpHistory')->name('login.ipHistory');
        Route::get('notification/history', 'notificationHistory')->name('notification.history');
        Route::get('email/detail/{id}', 'emailDetails')->name('email.details');
    });

    // Credit Assessment
    Route::controller(CreditAssessmentController::class)
    ->prefix('creditassessment')
    ->name('creditassessment.')
    ->group(function () {
        Route::get('new', 'newAssessments')->name('new');
        Route::delete('delete/{id}', 'deleteDetail')->name('delete');
    });


     // Loan Leads
     Route::get('utm-tracking', [UTMController::class, 'index'])->name('utm.tracking');
     Route::get('campaign-ids', [UTMController::class, 'getCampaignIds'])->name('campaign.ids');
     Route::controller(LeadController::class)
        ->prefix('leads')
        ->name('leads.')
        ->group(function () {
        Route::get('leads-all', 'leadsAll')->name('all');
        Route::get('leads-wbs', 'leadsWBS')->name('wbs');
        Route::get('leads-bsa', 'leadsBSA')->name('bsa');
        Route::get('leads-notinterested', 'leadsNotInterested')->name('notinterested');
        Route::get('verify/{id}', 'leadsVerify')->name('verify');
        Route::delete('delete/{id}', 'deleteLead')->name('delete');
     });

    // Loan Leads
    Route::controller(DecisionController::class)
        ->prefix('decision')
        ->name('decision.')
        ->group(function () {
            Route::get('decision-approved', 'decisionApproved')->name('approved');
            Route::get('decision-pendingdisbursed', 'decisionpendingDisbursed')->name('pendingdisbursed');
            Route::get('decision-disbursed', 'decisionDisbursed')->name('disbursed');
            Route::get('decision-rejected', 'decisionRejected')->name('rejected');
            Route::get('decision-closed', 'decisionClosed')->name('closed');
            Route::get('decision-pendingHold', 'decisionPendingHold')->name('pendingHold');
            Route::get('decision-approvedNotInterested', 'decisionApprovedNotInterested')->name('approvedNotInterested');
    });

    // Loan Leads
    Route::controller(CollectionController::class)
        ->prefix('collection')
        ->name('collection.')
        ->group(function () {
        Route::get('collection-predue', 'collectionPredue')->name('predue');
        Route::get('collection-overdue', 'collectionOverdue')->name('overdue');
    });


    Route::post('/loan-approval/store', [LoanApprovalController::class, 'store'])->name('loan.approval.store');
    Route::post('/loan-disbursal/store', [LoanDisbursalController::class, 'store'])->name('loan.disbursal.store');
    Route::post('/loan-utr/store', [LoanUtrController::class, 'store'])->name('loan.utr.store');

    // Admin Support
    Route::controller(SupportTicketController::class)
    ->prefix('ticket')
    ->name('ticket.')
    ->group(function () {
        Route::get('/', 'tickets')->name('index');
        Route::get('pending', 'pendingTicket')->name('pending');
        Route::get('closed', 'closedTicket')->name('closed');
        Route::get('answered', 'answeredTicket')->name('answered');
        Route::get('view/{id}', 'ticketReply')->name('view');
        Route::post('reply/{id}', 'replyTicket')->name('reply');
        Route::post('close/{id}', 'closeTicket')->name('close');
        Route::get('download/{ticket}', 'ticketDownload')->name('download');
        Route::post('delete/{id}', 'ticketDelete')->name('delete');
    });


    // Language Manager
    Route::controller(LanguageController::class)
    ->prefix('language')
    ->name('language.')
    ->group(function () {
        Route::get('/', 'langManage')->name('manage');
        Route::post('/', 'langStore')->name('manage.store');
        Route::post('delete/{id}', 'langDelete')->name('manage.delete');
        Route::post('update/{id}', 'langUpdate')->name('manage.update');
        Route::get('edit/{id}', 'langEdit')->name('key');
        Route::post('import', 'langImport')->name('import.lang');
        Route::post('store/key/{id}', 'storeLanguageJson')->name('store.key');
        Route::post('delete/key/{id}', 'deleteLanguageJson')->name('delete.key');
        Route::post('update/key/{id}', 'updateLanguageJson')->name('update.key');
        Route::get('get-keys', 'getKeys')->name('get.key');
    });


    Route::controller(GeneralSettingController::class)->group(function () {
        // General Setting
        Route::get('general-setting', 'index')->name('setting.index');
        Route::post('general-setting', 'update')->name('setting.update');
    
        // Configuration
        Route::get('setting/system-configuration', 'systemConfiguration')->name('setting.system.configuration');
        Route::post('setting/system-configuration', 'systemConfigurationSubmit');
    
        // Logo & Icon
        Route::get('setting/logo-icon', 'logoIcon')->name('setting.logo.icon');
        Route::post('setting/logo-icon', 'logoIconUpdate')->name('setting.logo.icon');
    
        // Custom CSS
        Route::get('custom-css', 'customCss')->name('setting.custom.css');
        Route::post('custom-css', 'customCssSubmit');
    
        // Cookie
        Route::get('cookie', 'cookie')->name('setting.cookie');
        Route::post('cookie', 'cookieSubmit');
    
        // Maintenance Mode
        Route::get('maintenance-mode', 'maintenanceMode')->name('maintenance.mode');
        Route::post('maintenance-mode', 'maintenanceModeSubmit');
    
        // Socialite Credentials
        Route::get('setting/social/credentials', 'socialiteCredentials')->name('setting.socialite.credentials');
        Route::post('setting/social/credentials/update/{key}', 'updateSocialiteCredential')->name('setting.socialite.credentials.update');
        Route::post('setting/social/credentials/status/{key}', 'updateSocialiteCredentialStatus')->name('setting.socialite.credentials.status.update');
    });
    

    //Cron Configuration
    Route::controller(CronConfigurationController::class)
    ->name('cron.')
    ->prefix('cron')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('store', 'store')->name('store');
        Route::post('update', 'update')->name('update');
        Route::post('delete/{id}', 'destroy')->name('delete');

        // Scheduling
        Route::get('schedule', 'schedule')->name('schedule');
        Route::post('schedule/store', 'scheduleStore')->name('schedule.store');
        Route::post('schedule/status/{id}', 'scheduleStatus')->name('schedule.status');
        Route::get('schedule/pause/{id}', 'schedulePause')->name('schedule.pause');
        Route::get('schedule/logs/{id}', 'scheduleLogs')->name('schedule.logs');

        // Log Management
        Route::post('schedule/log/resolved/{id}', 'scheduleLogResolved')->name('schedule.log.resolved');
        Route::post('schedule/log/flush/{id}', 'logFlush')->name('log.flush');
    });


    //KYC setting
    Route::controller(KycController::class)->group(function () {
        Route::get('kyc-setting', 'setting')->name('kyc.setting');
        Route::post('kyc-setting', 'settingUpdate');
    });


    //Loan-Category
    Route::controller(CategoryController::class)->prefix('category')->name('category.')->group(function () {
        Route::get('index', 'index')->name('index');
        Route::post('store/{id?}', 'store')->name('store');
        Route::post('status/{id}', 'status')->name('status');
    });
    


    Route::name('plans.loan.')->prefix('plans/loans')->controller(LoanPlanController::class)->group(function () {
        Route::get('/index', 'index')->name('index');
        Route::get('create', 'create')->name('create');
        Route::get('edit/{id}', 'edit')->name('edit');
        Route::post('store/{id?}', 'store')->name('save');
        Route::post('status/{id}', 'changeStatus')->name('status');
    });

    Route::name('creditbureau.')->prefix('creditbureau')->controller(CreditBureauController::class)->group(function () {
        Route::get('/index', 'index')->name('index');
        Route::get('/checkReport', 'checkReportByExperian')->name('checkReport');
        Route::get('/checkBankAccNo', 'checkBankAccNoByApproval')->name('checkBankAccNo');
        Route::get('/show/{id}', 'show')->name('show');

    });

    Route::name('experiancreditbureau.')->prefix('experiancreditbureau')->controller(ExperianCreditBureauController::class)->group(function () {
        Route::get('/index', 'index')->name('index');
    });

    Route::name('creditbureau.')->prefix('creditbureau')->controller(ScoreMeWebhookController::class)->group(function () {
        Route::post('/scoremeuploaddoc', 'checkBSAReportByScoreMe')->name('scoremeuploaddoc');
    });
    
    Route::name('digitap.')->prefix('digitap')->controller(DigitapController::class)->group(function () {
        Route::post('/digitapbsuploaddoc', 'checkBSAReportByDigitap')->name('digitapbsuploaddoc');
        Route::post('/digitapbsuploaddocstatus', 'checkStatusBSAReportByDigitap')->name('digitapbsuploaddocstatus');
        Route::get('/bsaDataShow/{id}', 'bsaDataShow')->name('bsaDataShow');
    });

    //============Loan================//
    Route::name('loan.')->prefix('loan')->controller(LoanController::class)->group(function () {
        Route::get('all', 'index')->name('index');
        Route::get('running', 'runningLoans')->name('running');
        Route::get('pending', 'pendingLoans')->name('pending');
        Route::get('rejected', 'rejectedLoans')->name('rejected');
        
        Route::get('paid', 'paidLoans')->name('paid');
        Route::get('due', 'dueInstallment')->name('due');
        Route::post('approve/{id}', 'approve')->name('approve');
        Route::post('reject/{id}', 'reject')->name('reject');
        Route::get('details/{id}', 'details')->name('details');
        Route::get('installments/{id}', 'installments')->name('installments');
        Route::get('reject-all', 'rejectAllLoans')->name('rejectAll'); // Retained this line
    });    



    //Notification Setting
    Route::name('setting.notification.')
    ->controller(NotificationController::class)
    ->prefix('notification')
    ->group(function () {

        // Template Setting
        Route::get('global', 'global')->name('global');
        Route::post('global/update', 'globalUpdate')->name('global.update');
        Route::get('templates', 'templates')->name('templates');
        Route::get('template/edit/{id}', 'templateEdit')->name('template.edit');
        Route::post('template/update/{id}', 'templateUpdate')->name('template.update');

        // Email Setting
        Route::get('email/setting', 'emailSetting')->name('email');
        Route::post('email/setting', 'emailSettingUpdate');
        Route::post('email/test', 'emailTest')->name('email.test');

        // SMS Setting
        Route::get('sms/setting', 'smsSetting')->name('sms');
        Route::post('sms/setting', 'smsSettingUpdate');
        Route::post('sms/test', 'smsTest')->name('sms.test');

        // Push Notification
        Route::get('push', 'push')->name('push');
        Route::post('push/setting', 'pushSetting')->name('push.setting');
    });



    // Plugin
    Route::controller(ExtensionController::class)
    ->prefix('extensions')
    ->name('extensions.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('update/{id}', 'update')->name('update');
        Route::post('status/{id}', 'status')->name('status');
    });



    //System Information
    Route::controller(SystemController::class)
    ->name('system.')
    ->prefix('system')
    ->group(function () {
        Route::get('info', 'systemInfo')->name('info');
        Route::get('server-info', 'systemServerInfo')->name('server.info');
        Route::get('optimize', 'optimize')->name('optimize');
        Route::get('optimize-clear', 'optimizeClear')->name('optimize.clear');
        Route::get('system-update', 'systemUpdate')->name('update');
        Route::post('update-upload', 'updateUpload')->name('update.upload');
    });


    // SEO
    Route::get('seo', [FrontendController::class, 'seoEdit'])->name('seo');


    // Frontend
    Route::name('frontend.')->prefix('frontend')->group(function () {

        Route::controller('FrontendController')->group(function () {
            Route::get('templates', 'templates')->name('templates');
            Route::post('templates', 'templatesActive')->name('templates.active');
            Route::get('frontend-sections/{key}', 'frontendSections')->name('sections');
            Route::post('frontend-content/{key}', 'frontendContent')->name('sections.content');
            Route::get('frontend-element/{key}/{id?}', 'frontendElement')->name('sections.element');
            Route::post('remove/{id}', 'remove')->name('remove');
        });

        // Page Builder
        Route::controller('PageBuilderController')->group(function () {
            Route::get('manage-pages', 'managePages')->name('manage.pages');
            Route::post('manage-pages', 'managePagesSave')->name('manage.pages.save');
            Route::post('manage-pages/update', 'managePagesUpdate')->name('manage.pages.update');
            Route::post('manage-pages/delete/{id}', 'managePagesDelete')->name('manage.pages.delete');
            Route::get('manage-section/{id}', 'manageSection')->name('manage.section');
            Route::post('manage-section/{id}', 'manageSectionUpdate')->name('manage.section.update');
        });
    });

    Route::controller(RoleController::class)
        ->name('roles.')
        ->group(function () {
            Route::get('roles/index', 'index')->name('index');
            Route::get('/roles/create', 'create')->name('create');
            Route::post('/roles', 'store')->name('store');
            Route::get('/roles/edit-permissions/{id}','editPermissions')->name('edit.permissions');
            Route::post('/roles/update-permissions/{id}','updatePermissions')->name('update.permissions');

    });

    Route::controller(EventLogController::class)
        ->name('eventlog.')
        ->group(function () {
            Route::get('eventlog/index', 'index')->name('index');

    });

    Route::controller(OSReportController::class)
        ->name('osreport.')
        ->group(function () {
            Route::get('osreport/index', 'index')->name('index');

    });

    Route::controller(AdminController::class)
        ->name('admins.')
        ->group(function () {
            Route::get('admins/index', 'index')->name('index');
            Route::get('admins/create', [AdminController::class, 'create'])->name('create');
            Route::post('admins/store', [AdminController::class, 'store'])->name('store');
            Route::get('admins/{admin}/edit', [AdminController::class, 'edit'])->name('edit');
            Route::post('admins/{admin}/update', [AdminController::class, 'update'])->name('update');

    });
});

