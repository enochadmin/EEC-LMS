<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Admin-only certificate branding: EEC logo, signatory name/title, the default
 * signature, and a per-user signature (one per instructor) that is picked up
 * automatically on the certificates that instructor issues.
 */
class BrandingController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:admin');
    }

    public function index()
    {
        $signatories = User::role(['instructor', 'admin'])
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'signature_path']);

        return view('admin.branding', [
            'signatoryName' => Setting::get(Setting::SIGNATORY_NAME),
            'signatoryTitle' => Setting::get(Setting::SIGNATORY_TITLE),
            'logoPath' => Setting::get(Setting::LOGO_PATH),
            'signaturePath' => Setting::get(Setting::SIGNATURE_PATH),
            'signatories' => $signatories,
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'signatory_name' => ['nullable', 'string', 'max:120'],
            'signatory_title' => ['nullable', 'string', 'max:120'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'signature' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        Setting::set(Setting::SIGNATORY_NAME, $data['signatory_name'] ?? null);
        Setting::set(Setting::SIGNATORY_TITLE, $data['signatory_title'] ?? null);

        if ($request->hasFile('logo')) {
            $this->deleteIfExists(Setting::get(Setting::LOGO_PATH));
            Setting::set(Setting::LOGO_PATH, $request->file('logo')->store('branding', 'public'));
        }

        if ($request->hasFile('signature')) {
            $this->deleteIfExists(Setting::get(Setting::SIGNATURE_PATH));
            Setting::set(Setting::SIGNATURE_PATH, $request->file('signature')->store('branding', 'public'));
        }

        return redirect()->back()->with('status', 'Certificate branding saved.');
    }

    /**
     * Attach a signature to a specific instructor. Certificates this user issues
     * will print it instead of the default signature.
     */
    public function signature(Request $request, User $user)
    {
        $request->validate([
            'signature' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        $this->deleteIfExists($user->signature_path);

        $user->update([
            'signature_path' => $request->file('signature')->store('signatures', 'public'),
        ]);

        return redirect()->back()->with('status', "Signature saved for {$user->name}.");
    }

    public function removeSignature(User $user)
    {
        $this->deleteIfExists($user->signature_path);

        $user->update(['signature_path' => null]);

        return redirect()->back()->with('status', "Signature removed for {$user->name}.");
    }

    protected function deleteIfExists(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
