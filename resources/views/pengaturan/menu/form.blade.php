<div class="mb-3">
    <label for="nama" class="form-label">Nama Menu</label>
    <input type="text" class="form-control @error('nama') is-invalid @enderror" name="nama" id="nama" value="{{ old('nama',$menu->nama ?? '') }}">
    <!-- Pesan error untuk nama -->
    @error('nama')
    <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
<div class="mb-3">
    <label for="routename" class="form-label">Route Name</label>
    <input type="text" class="form-control @error('routename') is-invalid @enderror" name="routename" id="routename" value="{{ old('routename',$menu->routename ?? '') }}">
    <!-- Pesan error untuk routename -->
    @error('routename')
    <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
<div class="mb-3">
    <label for="icon" class="form-label">Icon</label>
    <input type="text" class="form-control @error('icon') is-invalid @enderror" name="icon" id="icon" value="{{ old('icon',$menu->icon ?? '') }}">
    <!-- Pesan error untuk icon -->
    @error('icon')
    <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
<div class="mb-3">
    <label for="jnsmenu" class="form-label">Jenis Menu</label>
    <select name="jnsmenu" id="jnsmenu" class="form-select @error('jnsmenu') is-invalid @enderror" onchange="setParentMenu()">
        <option value="menu" {{ old('jnsmenu', $menu->jnsmenu ?? 'menu') == 'menu' ? 'selected' : '' }}>Menu</option>
        <option value="child" {{ old('jnsmenu', $menu->jnsmenu ?? 'child') == 'child' ? 'selected' : '' }}>Child Menu</option>
    </select>
    @error('jnsmenu')
    <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
<div class="mb-3">
    <label for="id_parent" class="form-label">Parent Menu</label>
    <select name="id_parent" id="id_parent" class="form-select @error('id_parent') is-invalid @enderror">
        <option value="0" {{ old('id_parent')==0 ? 'selected' : '' }}>Tidak Ada Parent</option>
        @foreach($parents as $dt)
        <option value="{{ $dt->id }}" {{ old('id_parent', $menu->id_parent ?? 0) == $dt->id ? 'selected' : '' }}>{{ $dt->nama }}</option>
        @endforeach
    </select>
    <!-- Pesan error untuk id_parent -->
    @error('id_parent')
    <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
<div class="mb-3">
    <label for="urutan" class="form-label">Urutan</label>
    <input type="number" class="form-control @error('urutan') is-invalid @enderror" name="urutan" id="urutan" value="{{ old('urutan',$menu->urutan ?? 0) }}">
    <!-- Pesan error untuk urutan -->
    @error('urutan')
    <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
<div class="mb-3">
    <label for="stts" class="form-label">Status</label>
    <select name="stts" id="stts" class="form-select @error('stts') is-invalid @enderror">
        <option value="N" {{ old('stts',$menu->stts ?? 'N')=='N' ? 'selected' : '' }}>Non Aktif</option>
        <option value="Y" {{ old('stts',$menu->stts ?? 'Y')=='Y' ? 'selected' : '' }}>Aktif</option>
    </select>
    <!-- Pesan error untuk stts -->
    @error('stts')
    <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>