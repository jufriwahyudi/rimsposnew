<?php

namespace App\Http\Controllers;

use App\Models\MenuList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class MenuListController extends Controller
{
    public $roleid;
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->roleid = session('selected_role');
            return $next($request);
        });
        // $this->middleware('roleAccess:menu.index')->only('index');
    }
    public function index()
    {
        // Ambil semua data menu yang statusnya aktif
        $menus = MenuList::where('id_parent', 0)
            ->orderBy('urutan')
            ->with('children') // Memuat relasi children
            ->get();
        return view('pengaturan.menu.index', compact('menus'));
    }

    public function create()
    {
        // Ambil data parent menu untuk dropdown
        $parents = MenuList::where('jnsmenu', 'menu')->orderBy('urutan')->get();
        return view('pengaturan.menu.create', compact('parents'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:200',
            'routename' => 'nullable|string|max:255',
            'icon' => 'nullable|string|max:50',
            'id_parent' => 'required|integer',
            'jnsmenu' => 'required|in:menu,child',
            'urutan' => 'required|integer',
            'stts' => 'required|in:Y,N',
        ]);

        MenuList::create($request->all());
        Cache::forget('menu_role_' . $this->roleid);

        return redirect()->route('menu.index')->with('success', 'Menu berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $id = Crypt::decryptString($id);
        $menu = MenuList::findOrFail($id);
        $parents = MenuList::where('jnsmenu', 'menu')->get();
        return view('pengaturan.menu.edit', compact('menu', 'parents'));
    }

    public function update(Request $request, $id)
    {
        $menu = MenuList::findOrFail($id);

        $request->validate([
            'nama' => 'required|string|max:200',
            'routename' => 'nullable|string|max:255',
            'icon' => 'nullable|string|max:50',
            'id_parent' => 'required|integer',
            'jnsmenu' => 'required|in:menu,child',
            'urutan' => 'required|integer',
            'stts' => 'required|in:Y,N',
        ]);

        $menu->update($request->all());
        Cache::forget('menu_role_' . $this->roleid);

        return redirect()->route('menu.index')->with('success', 'Menu berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $menu = MenuList::findOrFail($id);
        DB::table('menuby_role')->where('menu_id', $menu->id)->delete();
        $menu->delete();
        Cache::forget('menu_role_' . $this->roleid);
        return redirect()->route('menu.index')->with('success', 'Menu berhasil dihapus.');
    }
}
