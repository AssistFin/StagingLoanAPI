<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Menu;
use App\Models\Submenu;

class RoleMenuPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Roles
        $roles = [
            'Superadmin',
            'Admin',
            'Sub Admin',
            'Credit Manager',
            'Credit Associate',
            'Collection Manager',
            'Telecaller',
        ];

        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName]);
        }

        // Menus and Submenus
        $menuStructure = [
            'Dashboard' => [
                'route' => 'admin.dashboard',
                'submenus' => [],
                'roles' => ['Superadmin','Admin'],
            ],
            'Manage Roles' => [
                'route' => 'admin.roles.index',
                'submenus' => [
                    ['name' => 'All Roles', 'route' => 'admin.roles.index'],
                ],
                'roles' => ['Superadmin','Admin'],
            ],
            'Manage Employee' => [
                'route' => 'admin.admins.index',
                'submenus' => [
                    ['name' => 'All Employee', 'route' => 'admin.admins.index'],
                ],
                'roles' => ['Superadmin','Admin'],
            ],
            'Leads' => [
                'route' => 'admin.leads.index',
                'submenus' => [
                    ['name' => 'Leads All', 'route' => 'admin.leads.all'],
                    ['name' => 'Leads WBS', 'route' => 'admin.leads.wbs'],
                    ['name' => 'Leads BSA', 'route' => 'admin.leads.bsa'],
                    ['name' => 'UTM Tracking', 'route' => 'admin.utm.tracking'],
                ],
                'roles' => ['Superadmin', 'Admin'],
            ],
            'Decision' => [
                'route' => 'admin.decision.index',
                'submenus' => [
                    ['name' => 'Approved', 'route' => 'admin.decision.approved'],
                    ['name' => 'Pending Disbursal', 'route' => 'admin.decision.pendingdisbursed'],
                    ['name' => 'Disbursed', 'route' => 'admin.decision.disbursed'],
                    ['name' => 'Rejected', 'route' => 'admin.decision.rejected'],
                ],
                'roles' => ['Superadmin', 'Admin', 'Sub Admin', 'Credit Manager'],
            ],
            'Collection' => [
                'route' => 'admin.collection.index',
                'submenus' => [
                    ['name' => 'Predue Collection', 'route' => 'admin.collection.predue'],
                    ['name' => 'Overdue Collection', 'route' => 'admin.collection.overdue'],
                    ['name' => 'Closed', 'route' => 'admin.decision.closed'],
                ],
                'roles' => ['Superadmin', 'Admin', 'Sub Admin', 'Collection Manager'],
            ],
            'Experian Credit Bureau' => [
                'route' => 'admin.creditbureau.index',
                'submenus' => [],
                'roles' => ['Superadmin', 'Admin'],
            ],
            'Credit Bureau Report' => [
                'route' => 'admin.creditbureau.index',
                'submenus' => [
                    ['name' => 'Experian Report', 'route' => 'admin.experiancreditbureau.index']
                ],
                'roles' => ['Superadmin', 'Admin'],
            ],

        ];

        foreach ($menuStructure as $menuName => $data) {
            $menu = Menu::firstOrCreate([
                'name' => $menuName,
                'slug' => strtolower(str_replace(' ', '_', $menuName)),
                'route' => $data['route'],
            ]);

            foreach ($data['roles'] as $roleName) {
                $role = Role::where('name', $roleName)->first();
                $menu->roles()->syncWithoutDetaching([$role->id]);
            }

            foreach ($data['submenus'] as $submenuData) {
                $submenu = $menu->submenus()->firstOrCreate([
                    'name' => $submenuData['name'],
                    'slug' => strtolower(str_replace(' ', '_', $submenuData['name'])),
                    'route' => $submenuData['route'],
                ]);

                foreach ($data['roles'] as $roleName) {
                    $role = Role::where('name', $roleName)->first();
                    $submenu->roles()->syncWithoutDetaching([$role->id]);
                }
            }
        }
    }
}
