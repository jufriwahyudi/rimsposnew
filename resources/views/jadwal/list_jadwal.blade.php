<div class="card mt-3">
    <div class="card-body">
        <form id="formTambahSesi">
            <div class="row g-3 align-items-end">

                <div class="col-md-3">
                    <label class="form-label fw-bold">Nama Sesi</label>
                    <input type="text" name="nama_sesi" class="form-control" placeholder="Contoh: Sesi 1" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-bold">Jam Mulai</label>
                    <input type="time" name="jam_mulai" class="form-control" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-bold">Jam Selesai</label>
                    <input type="time" name="jam_selesai" class="form-control" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-bold">Kuota</label>
                    <input type="number" name="kuota_sesi" class="form-control" min="1" placeholder="Jumlah peserta" required>
                </div>
            </div>

            <div class="d-flex justify-content-end mt-4">
                <button type="submit" class="btn btn-primary">
                    Simpan
                </button>
            </div>
        </form>
    </div>
</div>
