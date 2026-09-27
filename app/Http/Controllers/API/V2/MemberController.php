<?php

namespace App\Http\Controllers\API\V2;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Member;
use App\Models\Bacenta;
use App\Models\Basonta;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Http\Controllers\API\V2\Concerns\ScopesByRole;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Support\Facades\Log;

class MemberController extends BaseController {
    use ScopesByRole;

    //
    public function index()
    {
        //Based on who is logged in, return the members that belong to the church/region/bacenta that the user belongs to.
        //Get Logged in user
        $user = Auth::user();

        if (!$user) {
            return $this->sendError('Unauthorised.', ['error'=>'User not found'], 401);
        }

        $members = $this->applyRoleScope(Member::query(), $user);

        return $this->sendResponse($members->get(), 'Members retrieved successfully.');
    }

    public function create(Request $request){

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'picture' => 'nullable|string',
            'phone' => 'nullable|string|max:255',
            'whatsapp' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'dob' => 'nullable|date',
            'gender' => 'nullable|array',
            'gender.*' => 'string|in:male,female',
            'marital_status' => 'nullable|array',
            'marital_status.*' => 'string|in:single,married',
            'occupation' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'gps_location' => 'nullable|string',
            'bacenta' => 'nullable|array',
            'bacenta.*' => 'integer|exists:bacentas,id',
        ]);

        $bacenta = isset($validated['bacenta'][0]) ? Bacenta::find($validated['bacenta'][0]) : null;

        // Stamp the full hierarchy so role-scoped reads (which filter on
        // members.region_id) can see the new member. When no bacenta was
        // chosen, fall back to the creating user's own region.
        $region = $bacenta?->region;
        if (!$bacenta) {
            $region = Auth::user()?->region;
        }

        $data = [
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
            'whatsapp' => $validated['whatsapp'] ?? null,
            'email' => $validated['email'] ?? null,
            'date_of_birth' => isset($validated['dob']) ? date('Y-m-d', strtotime($validated['dob'])) : null,
            'gender' => isset($validated['gender'][0]) ? $validated['gender'][0] : null,
            'marital_status' => isset($validated['marital_status'][0]) ? $validated['marital_status'][0] : null,
            'occupation' => $validated['occupation'] ?? null,
            'address' => $validated['address'] ?? null,
            'bacenta_id' => isset($validated['bacenta'][0]) ? $validated['bacenta'][0] : null,
            'basonta_id' => isset($validated['basonta'][0]) ? $validated['basonta'][0] : null,
            'region_id' => $region?->id,
            'zone_id' => $bacenta?->zone?->id,
            'stream_id' => $region?->stream_id,
        ];

        if (!empty($validated['picture'])) {
            $result = Cloudinary::upload($validated['picture'], [
                'folder' => 'members',
                'resource_type' => 'image',
            ]);
            $data['img_url'] = $result->getSecurePath();
        }

        $member = Member::create($data);

        return $this->sendResponse($member, 'Member created successfully.');
    }

    public function show($id)
    {
        [$member, $error] = $this->getAccessibleMember($id);
        if ($error) {
            return $error;
        }

        return $this->sendResponse($member->load(['bacenta', 'region', 'stream']), 'Member retrieved successfully.');
    }

    public function update(Request $request, $id)
    {
        [$member, $error] = $this->getAccessibleMember($id);
        if ($error) {
            return $error;
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'phone' => 'nullable|string|max:255',
            'whatsapp' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'dob' => 'nullable|date',
            'date_of_birth' => 'nullable|date',
            'occupation' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'gps_location' => 'nullable|string',
            'img_url' => 'nullable|string',
            'bacenta_id' => 'nullable|integer|exists:bacentas,id',
            'basonta_id' => 'nullable|integer|exists:basontas,id',
        ]);

        $data = $validated;

        if (array_key_exists('dob', $data)) {
            $data['date_of_birth'] = $data['dob'] ? date('Y-m-d', strtotime($data['dob'])) : null;
            unset($data['dob']);
        }

        foreach (['gender', 'marital_status'] as $field) {
            if ($request->has($field)) {
                $value = $request->get($field);
                $data[$field] = is_array($value) ? ($value[0] ?? null) : $value;
            }
        }

        foreach (['bacenta' => 'bacenta_id', 'basonta' => 'basonta_id'] as $input => $column) {
            if ($request->has($input)) {
                $value = $request->get($input);
                if (is_array($value)) {
                    $value = $value[0] ?? null;
                }
                $data[$column] = $value ? (int) $value : null;
            }
        }

        if (array_key_exists('bacenta_id', $data) && $data['bacenta_id']) {
            $bacenta = Bacenta::find($data['bacenta_id']);

            if (!$bacenta) {
                return $this->sendError('Bacenta not found.', ['error' => 'Bacenta not found'], 404);
            }

            $region = $bacenta->region;
            $data['region_id'] = $region?->id;
            $data['zone_id'] = $bacenta->zone?->id;
            $data['stream_id'] = $region?->stream_id;

            // A lead may not move a member into a bacenta outside their scope.
            if (!$this->recordInScope((object) $data, Auth::user())) {
                return $this->sendError('Unauthorized', ['error' => 'You cannot move this member outside your scope.'], 403);
            }
        }

        $member->update($data);

        return $this->sendResponse($member->fresh(['bacenta', 'region', 'stream']), 'Member updated successfully.');
    }

    public function destroy($id)
    {
        [$member, $error] = $this->getAccessibleMember($id);
        if ($error) {
            return $error;
        }

        $member->delete();

        return $this->sendResponse(null, 'Member deleted successfully.');
    }

    /**
     * Resolve a member the current user may access, following the same
     * scoping rules as index().
     *
     * @return array{0: Member|null, 1: \Illuminate\Http\JsonResponse|null}
     */
    private function getAccessibleMember($id): array
    {
        $member = Member::find($id);

        if (!$member) {
            return [null, $this->sendError('Member not found.', ['error' => 'Member not found'], 404)];
        }

        if (!$this->recordInScope($member, Auth::user())) {
            return [null, $this->sendError('Unauthorized', ['error' => 'You do not have access to this member.'], 403)];
        }

        return [$member, null];
    }
}