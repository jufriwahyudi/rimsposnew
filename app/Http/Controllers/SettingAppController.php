<?php

namespace App\Http\Controllers;

use App\Models\MenubyRole;
use App\Models\RoleMaster;
use App\Models\RoleUser;
use App\Models\SettingWhatsapp;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Session;

class SettingAppController extends Controller
{
    public $roleid;
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->roleid = session('selected_role');
            return $next($request);
        });
    }
    /**
     * Menampilkan daftar role yang ada beserta data menu dan user terkait.
     *
     * Fungsi ini mengambil semua role yang memiliki status aktif ('stts' = 'Y')
     * dan melakukan eager loading terhadap relasi `menus` dan `users`.
     * Data role kemudian dikirimkan ke view `pages.pengaturan.role.index` untuk ditampilkan.
     *
     * @param  \Illuminate\Http\Request  $request  Instans request HTTP.
     * @return \Illuminate\View\View  View yang menampilkan daftar role.
     */
    public function index(Request $request)
    {
        $selectedRole = session('selected_role');

        $roles = RoleMaster::with(['menus', 'users'])
            ->where('stts', 'Y')
            // ketika role superadmin, tampilkan semua role, selain itu tampilkan role yang memiliki store_id yang sama dengan session store_id
            ->when($selectedRole != 1, function ($query) {
                $query->where('store_id', session('store_id'));
            })
            ->get();
        return view('pengaturan.role.index', [
            "roles" => $roles
        ]);
    }

    /**
     * Menyimpan role baru ke dalam database.
     *
     * Fungsi ini memvalidasi input `nama_role` dari request, lalu membuat instans
     * dari model `RoleMaster` dan menyimpannya ke dalam database. Jika penyimpanan
     * berhasil, fungsi ini akan mengembalikan respon JSON yang menunjukkan keberhasilan.
     * Jika penyimpanan gagal, fungsi ini akan mengembalikan respon JSON yang menunjukkan kegagalan.
     *
     * @param  \Illuminate\Http\Request  $request  Instans request HTTP yang berisi data input.
     * @return \Illuminate\Http\JsonResponse  Respon JSON yang menunjukkan status keberhasilan atau kegagalan.
     */
    public function store(Request $request)
    {
        // Validasi input
        $request->validate([
            'nama_role' => 'required|string|max:200',
            'jenis_role' => 'required|string|in:ADMIN,WAREHOUSE,STORE',
        ]);

        // Simpan role ke database
        $role = new RoleMaster();
        if (session('selected_role') != 1) {
            $role->store_id = session('store_id');
        }
        $role->nama = $request->input('nama_role');
        $role->role_type = $request->input('jenis_role');
        if ($role->save()) {
            return response()->json(['success' => true, 'title' => 'Berhasil', 'message' => 'Berhasil menyimpan role', 'icon' => 'success']);
        } else {
            return response()->json(['success' => false, 'title' => 'Gagal', 'message' => 'Gagal menyimpan role', 'icon' => 'error']);
        }
    }

    /**
     * Menampilkan detail role beserta menu yang terkait untuk diedit.
     *
     * Fungsi ini menerima ID role yang dienkripsi, mendekripsinya, dan mengambil data role
     * beserta menu yang terkait dari database. Menu utama akan ditampilkan beserta sub-menu
     * yang dihubungkan dengan relasi `children` untuk hierarki menu.
     * Hasilnya akan dikirimkan ke view `pages.pengaturan.role.show`.
     *
     * @param  string  $id  ID role yang dienkripsi.
     * @return \Illuminate\View\View  View yang menampilkan detail role beserta daftar menu.
     */
    public function showrole(String $id)
    {
        $id = Crypt::decryptString($id);
        $selectedRole = $id;

        $data['role'] = RoleMaster::with(['menus', 'users.pengguna'])->find($id);
        $data['menus'] = \App\Models\MenuList::select('menu_list.*', \DB::raw('IF(menuby_role.id IS NULL, 0, 1) as has_access'))
            ->leftJoin('menuby_role', function ($join) use ($selectedRole) {
                $join->on('menu_list.id', '=', 'menuby_role.menu_id')->where('menuby_role.role_id', '=', $selectedRole);
            })
            ->where('menu_list.stts', 'Y')
            ->where('menu_list.id_parent', '0')
            ->orderBy('menu_list.urutan')
            ->with(['children' => function ($query) use ($selectedRole) {
                $query->select('menu_list.*', \DB::raw('IF(menuby_role.id IS NULL, 0, 1) as has_access'))
                    ->leftJoin('menuby_role', function ($join) use ($selectedRole) {
                        $join->on('menu_list.id', '=', 'menuby_role.menu_id')
                            ->where('menuby_role.role_id', '=', $selectedRole);
                    })
                    ->where('menu_list.stts', 'Y');
            }])
            ->get();
        // dd($selectedRole);
        return view('pengaturan.role.show', $data);
    }

    /**
     * Menghapus role yang dipilih dari database.
     *
     * Fungsi ini menerima ID role yang dienkripsi, mendekripsinya, dan mencoba menghapus
     * data role tersebut dari tabel `role_user`. Jika penghapusan berhasil, fungsi ini
     * akan mengembalikan respon JSON yang menunjukkan keberhasilan. Jika terjadi kesalahan,
     * fungsi ini akan mengembalikan respon JSON yang menunjukkan pesan error.
     *
     * @param  string  $id  ID role yang dienkripsi.
     * @return \Illuminate\Http\JsonResponse  Respon JSON yang menunjukkan status keberhasilan atau kegagalan.
     */
    public function destroyrole($id)
    {
        try {
            // Dekripsi ID yang dienkripsi
            $roleId = Crypt::decryptString($id);

            // Hapus data dari database
            $role = RoleUser::findOrFail($roleId);
            $role->delete();

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Simpan Role User
     *
     * Fungsi ini menerima `role_id` dan `user_id` yang akan di simpan ke table role_user. 
     *
     * @param  \Illuminate\Http\Request  $request The HTTP request instance.
     * @param  int  $request->role_id  Role id yang akan diberikan akses ke user.
     * @param  array  $request->user_id  User id yang akan diberikan akses ke role.
     * @return \Illuminate\Http\JsonResponse  A JSON response indicating success or failure.
     */
    public function storeroleuser(Request $request)
    {
        $validated = $request->validate([
            'role_id' => 'required|exists:role_master,id',
            'user_id' => 'required|exists:users,id',
        ]);

        RoleUser::where($validated)->delete();
        RoleUser::create($validated);
        Cache::forget('role_list_' . Auth::user()->id);

        return response()->json(['success' => 'User berhasil ditambahkan ke role.']);
    }

    /**
     * Menetapkan akses menu untuk sebuah role berdasarkan checkbox yang dipilih.
     *
     * Fungsi ini menerima array `menu_id` dan `role_id` serta memperbarui tabel `menu_by_roles`.
     * Fungsi ini pertama-tama menghapus record yang ada untuk role tersebut, lalu memasukkan menu yang baru.
     *
     * @param  \Illuminate\Http\Request  $request  Instans request HTTP.
     * @param  int  $request->role_id  ID role yang akan diberikan akses menu.
     * @param  array  $request->menu_ids  Array ID menu yang akan diberikan ke role.
     * @return \Illuminate\Http\JsonResponse  Respon JSON yang menunjukkan keberhasilan atau kegagalan.
     */
    public function storemenuuser(Request $request)
    {
        $this->validate($request, [
            'data' => 'required|array',
            'data.*.role_id' => 'required|integer|exists:role_master,id', // Pastikan role_id ada di tabel roles
            'data.*.menu_id' => 'required|integer|exists:menu_list,id' // Pastikan menu_id ada di tabel menus
        ]);

        $menuData = $request->input('data');

        // Menghapus akses menu yang lama terlebih dahulu (jika diperlukan)
        $role_id = $menuData[0]['role_id']; // Asumsi semua `role_id` dalam array sama
        MenubyRole::where('role_id', $role_id)->delete();
        // menu_role_
        try {
            MenuByRole::insert($menuData); // Masukkan data secara massal
            Cache::forget('menu_role_' . $role_id);
            return response()->json(['success' => true, 'message' => 'Data berhasil disimpan.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Data gagal disimpan: ' . $e->getMessage()]);
        }
    }

    /**
     * Mengubah session untuk selected_role.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function setSessionRole(Request $request)
    {
        // Validasi input role_id
        $roleid = Crypt::decryptString($request->input('role_id'));

        try {
            // Set session dengan role_id yang diberikan
            Session::put('selected_role', $roleid);

            return response()->json(['success' => true, 'message' => 'Role berhasil diubah.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    public function setSessionDivisi(Request $request)
    {
        // Validasi input role_id
        $divisiid = Crypt::decryptString($request->input('divisiid'));

        try {
            // Set session dengan role_id yang diberikan
            Session::put('divisi_kerja', $divisiid);

            return response()->json(['success' => true, 'message' => 'Divisi berhasil diubah.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function search(Request $request)
    {
        $search = $request->search;
        $storeId = session('store_id');
        $selectedRole = session('selected_role');
        $role = \App\Models\RoleMaster::find($selectedRole);
        $isSuperAdmin = $role && strtoupper($role->role_type) === 'SUPERADMIN';

        $query = User::orderby('name', 'asc');

        // Scope to active store for non-superadmins
        if (!$isSuperAdmin && $storeId) {
            $query->whereHas('stores', function ($q) use ($storeId) {
                $q->where('stores.id', $storeId);
            });
        }

        if ($search != '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        $users = $query->limit(5)->get();

        // dd($users);

        $response = [];
        foreach ($users as $user) {
            $response[] = [
                'id' => $user->id,
                'text' => $user->name . ' (' . $user->email . ')',
            ];
        }

        return response()->json($response);
    }

    public function settingParameter()
    {
        // Tampilkan halaman pengaturan WhatsApp

        return view('broadcast.pengaturanwatzap');
    }
    public function updateWatzap(Request $request)
    {
        SettingWhatsapp::set('watzap_api_key', $request->input('watzap_api_key'));
        SettingWhatsapp::set('watzap_number_key', $request->input('watzap_number_key'));
        SettingWhatsapp::set('watzap_number_key_catering', $request->input('watzap_number_key_catering'));

        return back()->with('success', 'Pengaturan WhatsApp berhasil disimpan.');
    }
}
