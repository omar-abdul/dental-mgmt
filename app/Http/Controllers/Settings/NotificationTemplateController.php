<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateNotificationTemplateRequest;
use App\Models\CommunicationTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class NotificationTemplateController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('manageNotificationTemplates');

        return Inertia::render('settings/NotificationTemplates', [
            'templates' => CommunicationTemplate::query()
                ->orderBy('code')
                ->get(['code', 'channel', 'name', 'body'])
                ->map(fn (CommunicationTemplate $template) => [
                    'code' => $template->code,
                    'channel' => $template->channel,
                    'name' => $template->name,
                    'body' => $template->body,
                ]),
            'placeholders' => ['{patient_name}', '{date}', '{time}', '{receipt_number}'],
        ]);
    }

    public function update(
        UpdateNotificationTemplateRequest $request,
        CommunicationTemplate $communicationTemplate,
    ): RedirectResponse {
        $communicationTemplate->update([
            'body' => $request->string('body')->value(),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Template updated.')]);

        return to_route('notification-templates.index');
    }
}
