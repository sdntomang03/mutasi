<form id="profile-form">
    @csrf
    <div class="field-grid">
        <label class="field"><span>Nama lengkap</span><input name="name" required maxlength="120" autocomplete="name" value="{{ auth()->user()->teacherProfile?->name ?? auth()->user()->name }}" placeholder="Nama guru"></label>
        <label class="field"><span>Nomor HP / WhatsApp</span><input name="phone" type="tel" required maxlength="24" autocomplete="tel" placeholder="08xxxxxxxxxx"></label>
        <label class="field"><span>Status kepegawaian</span>
            <select name="employment_type" required>
                <option value="">Pilih status</option><option value="PNS">PNS</option><option value="PPPK">PPPK</option><option value="KKI">KKI</option>
            </select>
        </label>
        <label class="field"><span>Jabatan saat ini</span>
            <select name="position" id="origin-position" required>
                <option value="">Pilih jabatan</option>
                @foreach (\App\Models\TeacherProfile::POSITIONS as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <label class="field"><span>Jenjang sekolah asal</span>
            <select name="level" id="origin-level" required>
                <option value="">Pilih jenjang</option>
                @foreach (\App\Models\TeacherProfile::LEVELS as $level)
                    <option value="{{ $level }}">{{ $level }}</option>
                @endforeach
            </select>
        </label>
        <label class="field" id="origin-subject-field" hidden><span>Mapel yang diampu</span>
            <select name="subject_id" id="origin-subject">
                <option value="">Pilih mapel</option>
                @foreach ($subjects as $subject)
                    <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                @endforeach
            </select>
        </label>
        <label class="field"><span>Sudin asal</span><select name="sudin_id" id="origin-sudin" required><option value="">Memuat Sudin...</option></select></label>
        <label class="field field-wide"><span>Nama sekolah asal</span><input name="school_name" required maxlength="160" placeholder="Contoh: SDN ..."></label>
        <label class="field field-wide"><span>Alamat sekolah asal</span><textarea name="school_address" required rows="2" maxlength="1000" placeholder="Alamat lengkap sekolah"></textarea></label>
    </div>

    <div class="section-divider"></div>
    <div class="section-title"><div><p class="eyebrow">LOKASI ASAL</p><h3>Di mana sekolahmu?</h3></div><span class="fixed-pill">DKI Jakarta</span></div>
    <div class="field-grid location-grid">
        <label class="field"><span>Kabupaten/kota</span><select id="origin-regency" required><option value="">Memuat wilayah...</option></select></label>
        <label class="field"><span>Kecamatan</span><select id="origin-district" required disabled><option value="">Pilih kabupaten/kota dulu</option></select></label>
        <label class="field field-wide"><span>Kelurahan</span><select id="origin-village" required disabled><option value="">Pilih kecamatan dulu</option></select></label>
    </div>

    <div class="section-divider"></div>
    <div class="section-title destination-title"><div><p class="eyebrow">TUJUAN MUTASI</p><h3>Ke mana kamu ingin pindah?</h3><p class="field-help">Pilih Sudin tujuan untuk memuat kecamatan sesuai cakupan yang tersimpan di database. Kamu bisa memilih semua kecamatan atau beberapa kecamatan tertentu.</p></div></div>
    <div class="field-grid destination-role-grid">
        <label class="field"><span>Jabatan yang dituju</span>
            <select name="destination_position" id="target-position" required>
                <option value="">Pilih jabatan tujuan</option>
                @foreach (\App\Models\TeacherProfile::POSITIONS as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <fieldset class="field level-field" id="target-levels">
            <span>Jenjang tujuan</span>
            <div class="level-options">
                @foreach (\App\Models\TeacherProfile::LEVELS as $level)
                    <label class="level-option"><input type="checkbox" value="{{ $level }}" data-target-level> {{ $level }}</label>
                @endforeach
            </div>
            <small class="field-help" id="target-level-help">Pilih jabatan tujuan terlebih dahulu. Guru mapel dapat memilih beberapa jenjang, guru kelas hanya satu jenjang.</small>
        </fieldset>
        <fieldset class="field level-field field-wide" id="target-subject-field" hidden>
            <span>Mapel tujuan</span>
            <div class="level-options">
                @foreach ($subjects as $subject)
                    <label class="level-option"><input type="checkbox" value="{{ $subject->id }}" data-target-subject> {{ $subject->name }}</label>
                @endforeach
            </div>
            <small class="field-help">Pilih satu atau beberapa mapel yang ingin kamu ampu di sekolah tujuan.</small>
        </fieldset>
    </div>
    <div class="destination-builder">
        <div class="field-grid">
            <label class="field field-wide"><span>Sudin tujuan</span><select id="target-sudin" required><option value="">Pilih Sudin tujuan</option></select></label>
            <label class="field"><span>Cakupan tujuan</span><select id="target-scope" disabled><option value="all">Semua kecamatan dalam Sudin</option><option value="districts">Pilih beberapa kecamatan</option></select></label>
            <label class="field" id="target-district-wrap" hidden><span>Kecamatan tujuan</span><select id="target-district" multiple disabled></select><small class="field-help">Pilih satu atau beberapa kecamatan. Tahan Ctrl (Windows) atau Cmd (Mac) untuk memilih beberapa.</small></label>
        </div>
        <button class="button button-secondary add-destination" id="add-destination" type="button"><span>＋</span> Terapkan tujuan mutasi</button>
    </div>
    <div class="selected-destinations" id="selected-destinations"><p class="empty-inline">Tujuan mutasi belum dipilih.</p></div>
    <button class="button button-primary submit-profile" type="submit"><span>Simpan profil</span><span aria-hidden="true">→</span></button>
    <p class="form-note">Jika ada kecocokan baru, notifikasi email dikirim kepada kedua guru. Detail kontak hanya tampil setelah login.</p>
</form>
