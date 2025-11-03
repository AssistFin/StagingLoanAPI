<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Models\LoanApplication;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Support\Facades\Response;
use App\Models\MessageTemplate;

class TemplateController extends Controller
{
    public function emailindex(Request $request)
    {
        $etemplate = MessageTemplate::where('type', 'email')->get();

        return view('admin.template.emailindex', compact('etemplate'));
    }

    public function createemail()
    {
        return view('admin.template.createemail');
    }

    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|in:email',
            'title' => 'required|string|max:255',
            'subject' => 'nullable|string|max:255',
            'body' => 'required|string',
        ]);

        MessageTemplate::create($request->all());
        return redirect()->route('admin.template.emailindex')->with('success', ucfirst($request->type).' template created successfully!');
    }

    public function editemailTemplates($id)
    {
        $emailTemp = MessageTemplate::findOrFail($id);

        return view('admin.template.edit-emailpermissions', compact('emailTemp'));
    }

    public function updateemailTemplates(Request $request, $id)
    {
        $updateETemp = MessageTemplate::updateOrCreate(
                [
                    'id' => $id,
                ],
                array_merge($request->all())
            );

        return redirect()->route('admin.template.emailindex')->with('success', 'template updated!');
    }

    public function smsindex(Request $request)
    {
        $smstemplate = MessageTemplate::where('type', 'sms')->get();

        return view('admin.template.smsindex', compact('smstemplate'));
    }

    public function activate(Request $request, $id)
    {
        // Find the template
        $template = MessageTemplate::findOrFail($id);

        // Deactivate only templates of the same type (email or sms)
        MessageTemplate::where(['type' => $template->type, 'subject' => $template->subject])
            ->update(['status' => 'inactive']);

        // Activate the selected one
        $template->status = 'active';
        $template->save();

        return response()->json([
            'success' => true,
            'active_id' => $template->id,
            'type' => $template->type,
            'message' => ucfirst($template->type) . ' template activated successfully!',
        ]);
    }

    public function createsms()
    {
        return view('admin.template.createsms');
    }

    public function storeSMS(Request $request)
    {
        $request->validate([
            'type' => 'required|in:sms',
            'title' => 'required|string|max:255',
            'subject' => 'nullable|string|max:255',
            'body' => 'required|string',
        ]);

        MessageTemplate::create($request->all());
        return redirect()->route('admin.template.emailindex')->with('success', ucfirst($request->type).' template created successfully!');
    }

    public function editsmsTemplates($id)
    {
        $smsTemp = MessageTemplate::findOrFail($id);

        return view('admin.template.edit-smspermissions', compact('smsTemp'));
    }

    public function updatesmsTemplates(Request $request, $id)
    {
        $updateETemp = MessageTemplate::updateOrCreate(
                [
                    'id' => $id,
                ],
                array_merge($request->all())
            );

        return redirect()->route('admin.template.smsindex')->with('success', 'template updated!');
    }
}
