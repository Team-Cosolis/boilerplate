<?php

namespace agungsugiarto\boilerplate\Controllers\Users;

use agungsugiarto\boilerplate\Controllers\BaseController;
use agungsugiarto\boilerplate\Models\Group;
use CodeIgniter\API\ResponseTrait;

class RoleController extends BaseController
{
    use ResponseTrait;

    /**
     * Return an array of resource objects, themselves in array format.
     *
     * @return array an array
     */
    public function index()
    {
        $data = [
            'title' => 'Role',
            'data'  => $this->authorize->permissions(),
        ];

        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'data' => $this->authorize->groups(),
            ]);
        }

        return view('agungsugiarto\boilerplate\Views\Role\index', $data);
    }

    /**
     * Return a new resource object, with default properties.
     *
     * @return array an array
     */
    public function new()
    {
        $data = [
            'title'=> 'Role',
            'data' => $this->authorize->permissions(),
        ];

        return view('agungsugiarto\boilerplate\Views\Role\create', $data);
    }

    /**
     * Return the properties of a resource object.
     *
     * @return array an array
     */
    public function show($id)
    {
        //
    }

    /**
     * Create a new resource object, from "posted" parameters.
     *
     * @return array an array
     */
    public function create()
    {
        $validationRules = [
            'name'        => 'required|min_length[5]|max_length[255]|is_unique[auth_groups.name]',
            'description' => 'required|max_length[255]',
            'permission'  => 'required',
        ];

        $name = $this->request->getPost('name');
        $description = $this->request->getPost('description');
        $permission = $this->request->getPost('permission');

        if (!$this->validate($validationRules)) {
            return redirect()->back()->withInput()->with('error', $this->validator->getErrors());
        }

        try {
            $this->db->transBegin();
            $id = $this->authorize->createGroup(url_title($name), $description);

            foreach ($permission as $value) {
                $this->authorize->addPermissionToGroup($value, $id);
            }
            $this->db->transCommit();
        } catch (\Exception $e) {
            $this->db->transRollback();

            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()->with('message', lang('Auth.loginSuccess'));
    }

    /**
     * Return the editable properties of a resource object.
     *
     * @return array an array
     */
    public function edit($id)
    {
        $data = [
            'title'       => 'Edit',
            'role'        => $this->authorize->group($id),
            'permissions' => $this->authorize->permissions(),
            'permission'  => (new Group())->getPermissionsForGroup($id),
            // 'test'        => $test,
        ];

        return view('agungsugiarto\boilerplate\Views\Role\edit', $data);
    }

    /**
     * Add or update a model resource, from "posted" properties.
     *
     * @return array an array
     */
    public function update($id = null)
    {
        $validationRules = [
            'name'        => 'required|min_length[5]|max_length[255]',
            'description' => 'required|max_length[255]',
            'permission'  => 'required',
        ];

        $name = $this->request->getPost('name');
        $description = $this->request->getPost('description');
        $permission = $this->request->getPost('permission');

        if (!$this->validate($validationRules)) {
            return redirect()->back()->withInput()->with('error', $this->validator->getErrors());
        }

        try {
            $this->db->transBegin();
            // update group
            $this->authorize->updateGroup($id, url_title($name), $description);

            // remove first all groups permissions
            $this->db->table('auth_groups_permissions')->where('group_id', $id)->delete();

            foreach ($permission as $value) {
                // insert with new permission to group
                $this->authorize->addPermissionToGroup($value, $id);
            }

            $this->db->transCommit();
        } catch (\Exception $e) {
            $this->db->transRollback();

            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', 'Success update!');
    }

    /**
     * Delete the designated resource object from the model.
     *
     * @return array an array
     */
    public function delete($id)
    {
        if (!$found = $this->authorize->deleteGroup($id)) {
            return $this->fail('fail deleted');
        }

        return $this->respondDeleted($found);
    }
}
