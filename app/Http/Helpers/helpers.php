<?php

use App\Constants\Status;
use App\Lib\GoogleAuthenticator;
use App\Models\Extension;
use App\Models\Frontend;
use App\Models\GeneralSetting;
use App\Models\AdminEventLog;
use Carbon\Carbon;
use App\Lib\Captcha;
use App\Lib\ClientInfo;
use App\Lib\CurlRequest;
use App\Lib\FileManager;
use App\Lib\Initials;
use App\Notify\Notify;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function systemDetails() {
    $system['name'] = 'Loanone';
    $system['version'] = '1.0';
    $system['build_version'] = '1.0.0';
    return $system;
}

function slug($string) {
    return Illuminate\Support\Str::slug($string);
}

function verificationCode($length) {
    if ($length == 0) return 0;
    $min = pow(10, $length - 1);
    $max = (int) ($min - 1) . '9';
    return random_int($min, $max);
}

function getNumber($length = 8) {
    $characters = '1234567890';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

function activeTemplate($asset = false) {
    $template = gs('active_template');
    if ($asset) return 'assets/templates/' . $template . '/';
    return 'templates.' . $template . '.';
}

function activeTemplateName() {
    $template = gs('active_template');
    return $template;
}

function loadReCaptcha() {
    return Captcha::reCaptcha();
}

function loadCustomCaptcha($width = '100%', $height = 46, $bgColor = '#003') {
    return Captcha::customCaptcha($width, $height, $bgColor);
}

function verifyCaptcha() {
    return Captcha::verify();
}

function loadExtension($key) {
    $extension = Extension::where('act', $key)->where('status', Status::ENABLE)->first();
    return $extension ? $extension->generateScript() : '';
}

function getTrx($length = 12) {
    $characters = 'ABCDEFGHJKMNOPQRSTUVWXYZ123456789';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

function getAmount($amount, $length = 2) {
    $amount = round($amount ?? 0, $length);
    return $amount + 0;
}

function showAmount($amount, $decimal = 2, $separate = true, $exceptZeros = false) {
    $separator = '';
    if ($separate) {
        $separator = ',';
    }
    $printAmount = number_format($amount, $decimal, '.', $separator);
    if ($exceptZeros) {
        $exp = explode('.', $printAmount);
        if ($exp[1] * 1 == 0) {
            $printAmount = $exp[0];
        } else {
            $printAmount = rtrim($printAmount, '0');
        }
    }
    return $printAmount;
}


function removeElement($array, $value) {
    return array_diff($array, (is_array($value) ? $value : array($value)));
}

function cryptoQR($wallet) {
    return "https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=$wallet&choe=UTF-8";
}

function keyToTitle($text) {
    return ucfirst(preg_replace("/[^A-Za-z0-9 ]/", ' ', $text));
}

function titleToKey($text) {
    return strtolower(str_replace(' ', '_', $text));
}

function strLimit($title = null, $length = 10) {
    return Str::limit($title, $length);
}

function getIpInfo() {
    $ipInfo = ClientInfo::ipInfo();
    return $ipInfo;
}

function osBrowser() {
    $osBrowser = ClientInfo::osBrowser();
    return $osBrowser;
}

function getTemplates() {
    $param['purchasecode'] = env("PURCHASECODE");
    $param['website'] = @$_SERVER['HTTP_HOST'] . @$_SERVER['REQUEST_URI'] . ' - ' . env("APP_URL");
    $url = 'https://license.viserlab.com/updates/templates/' . systemDetails()['name'];
    $response = CurlRequest::curlPostContent($url, $param);
    if ($response) {
        return $response;
    } else {
        return null;
    }
}

function getPageSections($arr = false) {
    $jsonUrl = resource_path('views/') . str_replace('.', '/', activeTemplate()) . 'sections.json';
    $sections = json_decode(file_get_contents($jsonUrl));
    if ($arr) {
        $sections = json_decode(file_get_contents($jsonUrl), true);
        ksort($sections);
    }
    return $sections;
}

function getImage($image, $size = null) {
    $clean = '';
    if (file_exists($image) && is_file($image)) {
        return asset($image) . $clean;
    }
    if ($size) {
        return route('placeholder.image', $size);
    }
    return asset('assets/images/default.png');
}

function authenticate() {
    $client = new Client();

    try {
        $response = $client->post('https://api.sandbox.co.in/authenticate', [
            'headers' => [
                'accept' => 'application/json',
                'x-api-key' => env('SANDBOX_API_KEY'),
                'x-api-secret' => env('SANDBOX_API_SECRET'),
                'x-api-version' => '1.0'
            ]
        ]);

        $data = json_decode($response->getBody(), true);
        return $data['access_token'] ?? null;
    } catch (GuzzleException $e) {
        Log::error('Authentication failed: ' . $e->getMessage());
        return null;
    }
}

function getAddressFromCoordinates($latitude, $longitude)
{
    $apiKey = env('GOOGLE_MAPS_API_KEY'); // Make sure to set this in your .env file
    $client = new Client();
    $url = "https://maps.googleapis.com/maps/api/geocode/json?latlng={$latitude},{$longitude}&key={$apiKey}";

    try {
        Log::info("Requesting address from Google Maps API: " . $url);

        $response = $client->request('GET', $url);
        $body = json_decode($response->getBody(), true);

        Log::info("Google Maps API response status: " . $response->getStatusCode());
        // Log::info("Google Maps API response body: " . json_encode($body));

        if ($response->getStatusCode() == 200 && isset($body['results'][0])) {
            return $body['results'][0]['formatted_address'];
        }

        return null;
    } catch (GuzzleException $e) {
        Log::error("Error fetching address from Google Maps API: " . $e->getMessage());
        return null;
    }
}

function notify($user, $templateName, $shortCodes = null, $sendVia = null, $createLog = true) {
    $general = gs();
    $globalShortCodes = [
        'site_name' => $general->site_name,
        'site_currency' => $general->cur_text,
        'currency_symbol' => $general->cur_sym,
    ];

    if (gettype($user) == 'array') {
        $user = (object) $user;
    }

    $shortCodes = array_merge($shortCodes ?? [], $globalShortCodes);

    $notify               = new Notify($sendVia);
    $notify->templateName = $templateName;
    $notify->shortCodes   = $shortCodes;
    $notify->user         = $user;
    $notify->createLog    = $createLog;
    $notify->userColumn   = isset($user->id) ? $user->getForeignKey() : 'user_id';
    $notify->send();
}

function getPaginate($paginate = 20) {
    return $paginate;
}

function paginateLinks($data) {
    return $data->appends(request()->all())->links();
}

function menuActive($routeName, $type = null, $param = null) {
    if ($type == 3) $class = 'side-menu--open';
    elseif ($type == 2) $class = 'sidebar-submenu__open';
    else $class = 'active';

    if (is_array($routeName)) {
        foreach ($routeName as $key => $value) {
            if (request()->routeIs($value)) return $class;
        }
    } elseif (request()->routeIs($routeName)) {
        if ($param) {
            $routeParam = array_values(@request()->route()->parameters ?? []);
            if (strtolower(@$routeParam[0]) == strtolower($param)) return $class;
            else return;
        }
        return $class;
    }
}

function fileUploader($file, $location, $size = null, $old = null, $thumb = null) {
    $fileManager = new FileManager($file);
    $fileManager->path = $location;
    $fileManager->size = $size;
    $fileManager->old = $old;
    $fileManager->thumb = $thumb;
    $fileManager->upload();
    return $fileManager->filename;
}

function fileManager() {
    return new FileManager();
}

function getFilePath($key) {
    return fileManager()->$key()->path;
}

function getFileSize($key) {
    return fileManager()->$key()->size;
}

function getFileExt($key) {
    return fileManager()->$key()->extensions;
}

function diffForHumans($date) {
    $lang = session()->get('lang');
    Carbon::setlocale($lang);
    return Carbon::parse($date)->diffForHumans();
}

function showDateTime($date, $format = 'Y-m-d h:i A') {
    $lang = session()->get('lang');
    Carbon::setlocale($lang);
    return Carbon::parse($date)->translatedFormat($format);
}

function showDateTimeView($datetime, $format = 'Y-m-d h:i A', $tz = 'Asia/Kolkata') {
    return Carbon::parse($datetime)->timezone($tz)->format($format);
}

function getContent($dataKeys, $singleQuery = false, $limit = null, $orderById = false) {
    if ($singleQuery) {
        $content = Frontend::where('data_keys', $dataKeys)->orderBy('id', 'desc')->first();
    } else {
        $article = Frontend::query();
        $article->when($limit != null, function ($q) use ($limit) {
            return $q->limit($limit);
        });
        if ($orderById) {
            $content = $article->where('data_keys', $dataKeys)->orderBy('id')->get();
        } else {
            $content = $article->where('data_keys', $dataKeys)->orderBy('id', 'desc')->get();
        }
    }
    return $content;
}

function gatewayRedirectUrl($type = false) {
    if ($type) {
        return 'user.deposit.history';
    } else {
        return 'user.deposit.index';
    }
}

function verifyG2fa($user, $code, $secret = null) {
    $authenticator = new GoogleAuthenticator();
    if (!$secret) {
        $secret = $user->tsc;
    }
    $oneCode = $authenticator->getCode($secret);
    $userCode = $code;
    if ($oneCode == $userCode) {
        $user->tv = 1;
        $user->save();
        return true;
    } else {
        return false;
    }
}

function urlPath($routeName, $routeParam = null) {
    if ($routeParam == null) {
        $url = route($routeName);
    } else {
        $url = route($routeName, $routeParam);
    }
    $basePath = route('home');
    $path = str_replace($basePath, '', $url);
    return $path;
}

function showMobileNumber($number) {
    $length = strlen($number);
    return substr_replace($number, '***', 2, $length - 4);
}

function showEmailAddress($email) {
    $endPosition = strpos($email, '@') - 1;
    return substr_replace($email, '***', 1, $endPosition);
}

function getRealIP() {
    $ip = $_SERVER["REMOTE_ADDR"];
    //Deep detect ip
    if (filter_var(@$_SERVER['HTTP_FORWARDED'], FILTER_VALIDATE_IP)) {
        $ip = $_SERVER['HTTP_FORWARDED'];
    }
    if (filter_var(@$_SERVER['HTTP_FORWARDED_FOR'], FILTER_VALIDATE_IP)) {
        $ip = $_SERVER['HTTP_FORWARDED_FOR'];
    }
    if (filter_var(@$_SERVER['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP)) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    if (filter_var(@$_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP)) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    }
    if (filter_var(@$_SERVER['HTTP_X_REAL_IP'], FILTER_VALIDATE_IP)) {
        $ip = $_SERVER['HTTP_X_REAL_IP'];
    }
    if (filter_var(@$_SERVER['HTTP_CF_CONNECTING_IP'], FILTER_VALIDATE_IP)) {
        $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
    }
    if ($ip == '::1') {
        $ip = '127.0.0.1';
    }

    return $ip;
}

function appendQuery($key, $value) {
    return request()->fullUrlWithQuery([$key => $value]);
}

function dateSort($a, $b) {
    return strtotime($a) - strtotime($b);
}

function dateSorting($arr) {
    usort($arr, "dateSort");
    return $arr;
}

function gs($key = null) {
    $general = Cache::get('GeneralSetting');
    if (!$general) {
        $general = GeneralSetting::first();
        Cache::put('GeneralSetting', $general);
    }
    if ($key) return @$general->$key;
    return $general;
}

function isImage($string) {
    $allowedExtensions = array('jpg', 'jpeg', 'png', 'gif');
    $fileExtension = pathinfo($string, PATHINFO_EXTENSION);
    if (in_array($fileExtension, $allowedExtensions)) {
        return true;
    } else {
        return false;
    }
}

function isHtml($string) {
    if (preg_match('/<.*?>/', $string)) {
        return true;
    } else {
        return false;
    }
}

function getFormData($formData) {
    return json_encode([
        'type'        => $formData->type,
        'is_required' => $formData->is_required,
        'label'       => $formData->name,
        'extensions'  => explode(',', $formData->extensions) ?? 'null',
        'options'     => $formData->options,
        'old_id'      => '',
    ]);
}

function createBadge($type, $text) {
    return "<span class='badge badge--$type'>" . trans($text) . '</span>';
}

function getInitials($name) {
    return Initials::generate($name);
}

function queryBuild($key, $value) {
    $queries = request()->query();
    if (@$queries['search']) {
        $route = route('user.transactions');
        unset($queries['search']);
    } else {
        $route = request()->getRequestUri();
    }
    if (count($queries) > 0) {
        $delimeter = '&';
    } else {
        $delimeter = '?';
    }
    if (request()->has($key)) {
        $url     = request()->getRequestUri();
        $pattern = "\?$key";
        $match   = preg_match("/$pattern/", $url);
        if ($match != 0) {
            return preg_replace('~(\?|&)' . $key . '[^&]*~', "\?$key=$value", $url);
        }
        $filteredURL = preg_replace('~(\?|&)' . $key . '[^&]*~', '', $url);
        return $filteredURL . $delimeter . "$key=$value";
    }
    return $route . $delimeter . "$key=$value";
}

function queryLoanBuild($key, $value){
    $queries = request()->query();


    if (@$queries['search']) {
        $route = route('user.loan.list');
        unset($queries['search']);
    } else {
        $route = request()->getRequestUri();
    }
    if (count($queries) > 0) {
        $delimeter = '&';
    } else {
        $delimeter = '?';
    }
    if (request()->has($key)) {
        $url     = request()->getRequestUri();
        $pattern = "\?$key";
        $match   = preg_match("/$pattern/", $url);
        if ($match != 0) {
            return preg_replace('~(\?|&)' . $key . '[^&]*~', "\?$key=$value", $url);
        }
        $filteredURL = preg_replace('~(\?|&)' . $key . '[^&]*~', '', $url);
        return $filteredURL . $delimeter . "$key=$value";
    }
    return $route . $delimeter . "$key=$value";
}

function sendMailViaSMTP($subject, $message, $to, $attachment = null){
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = config('services.smtp.host'); // e.g., smtp.gmail.com
        $mail->SMTPAuth   = true;
        $mail->isHTML(true);
        $mail->Username   = config('services.smtp.username');
        $mail->Password   = config('services.smtp.password');
        $mail->SMTPSecure = config('services.smtp.encryption'); // or 'ssl'
        $mail->Port       = config('services.smtp.port');   // or 465

        $mail->setFrom(config('services.smtp.address'), config('services.smtp.name'));
        if($to){
            $mail->addAddress($to);
            //$mail->addCC('tech.assistfin@gmail.com');
        }else{
            $mail->addAddress('tech.assistfin@gmail.com');
        }
        if($attachment){
            $mail->addAttachment($attachment);
        }
        $mail->Subject = $subject;
        $mail->Body    = $message;
        //$mail->SMTPDebug = 3;
        $mail->send();
        //echo 'Message has been sent';
        return true;
    } catch (Exception $e) {
        //echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        return "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }

    function generateRefNo()
    {
        // Get current financial year
        $now = Carbon::now();
        $month = $now->month;

        if ($month >= 4) {
            // FY starts in April
            $fyStart = $now->year;
            $fyEnd = $now->year + 1;
        } else {
            $fyStart = $now->year - 1;
            $fyEnd = $now->year;
        }

        $fyString = $fyStart . '-' . substr($fyEnd, -2); // e.g. 2024-25

        $currentMonth = str_pad($month, 2, '0', STR_PAD_LEFT);

        // Count closed loans this FY + month
        $count = DB::table('loan_applications')
            ->where('loan_closed_status', 'closed')
            ->whereYear('loan_closed_date', '>=', $fyStart)
            ->whereYear('loan_closed_date', '<=', $fyEnd)
            ->whereMonth('loan_closed_date', $month)
            ->count();

        $next = str_pad($count + 1, 5, '0', STR_PAD_LEFT);

        return "{$fyString}/{$currentMonth}/{$next}";
    }
}

function eventLog($admin_id = null, $user_id, $event, $description = null)
{
    AdminEventLog::create([
        'admin_id'   => Auth::guard('admin')->id() ? Auth::guard('admin')->id() : $admin_id,
        'user_id'    => $user_id,
        'event'      => $event,
        'description'=> $description,
        'ip_address' => request()->header('X-Forwarded-For') ?? request()->ip(),
        'user_agent' => Request::header('User-Agent')
    ]);
}

