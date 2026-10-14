<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#f7f3ed">
    <title>طلب موعد | {{ $shopName }}</title>
    <style>
        :root{--bg:#f7f3ed;--surface:#fffdf9;--text:#302a25;--muted:#786f67;--line:#e9e1d8;--accent:#9a6c45;--danger:#a33a35}
        *{box-sizing:border-box}
        body{margin:0;background:var(--bg);color:var(--text);font-family:Arial,sans-serif;line-height:1.7}
        a{color:inherit;text-decoration:none}
        .container{width:min(760px,calc(100% - 32px));margin:0 auto}
        header{padding:24px 0}
        .brand{font-size:1.15rem;font-weight:700}
        main{padding:12px 0 56px}
        .booking-card{padding:clamp(22px,5vw,40px);border:1px solid var(--line);border-radius:22px;background:var(--surface);box-shadow:0 22px 55px rgba(64,45,30,.08)}
        h1{margin:0 0 8px;font-size:clamp(1.7rem,5vw,2.35rem);line-height:1.3}
        .intro{margin:0 0 24px;color:var(--muted)}
        .notice{margin-bottom:22px;padding:13px 15px;border:1px solid #e8d9c6;border-radius:12px;background:#f8f0e6;color:#705236;font-size:.9rem}
        .booking-form{display:grid;gap:18px}
        .field{display:grid;gap:7px}
        .field label,.field legend{font-weight:700;font-size:.94rem}
        .field input,.field select,.field textarea{width:100%;min-height:48px;padding:10px 12px;border:1px solid #d9cec2;border-radius:10px;background:#fff;color:var(--text);font:inherit}
        .field textarea{min-height:96px;resize:vertical}
        .field input:focus,.field select:focus,.field textarea:focus{outline:3px solid rgba(154,108,69,.22);border-color:var(--accent)}
        fieldset{min-width:0;margin:0;padding:0;border:0}
        .service-options{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:9px}
        .service-option{display:flex;align-items:flex-start;gap:10px;padding:12px;border:1px solid var(--line);border-radius:11px;background:#fff}
        .service-option[hidden]{display:none}
        .service-option input{width:18px;min-height:18px;margin:3px 0 0;accent-color:var(--accent)}
        .service-details{display:grid;gap:3px}
        .service-details small{color:var(--muted);font-size:.8rem}
        .field-error{color:var(--danger);font-size:.84rem}
        .empty-services{margin:0;color:var(--muted)}
        .button{display:inline-flex;min-height:48px;align-items:center;justify-content:center;padding:0 20px;border:0;border-radius:11px;background:var(--text);color:#fff;font:inherit;font-weight:700;cursor:pointer}
        .button:hover{background:#765035}
        .back-link{display:inline-block;margin-top:20px;color:var(--accent);font-weight:700}
        @media(max-width:540px){.service-options{grid-template-columns:1fr}.booking-card{border-radius:16px}}
    </style>
</head>
<body>
    <header class="container">
        <a class="brand" href="{{ route('tenant.landing') }}">{{ $shopName }}</a>
    </header>
    <main class="container">
        <section class="booking-card" aria-labelledby="booking-title">
            <h1 id="booking-title">اطلب موعدك</h1>
            <p class="intro">اختر الفرع والخدمات والموظف والوقت الذي تفضله.</p>
            <p class="notice">هذا طلب موعد مبدئي وليس تأكيدًا نهائيًا. سيتواصل معك الصالون لتأكيد الوقت.</p>

            @if($branches->isEmpty())
                <p class="empty-services">لا توجد فروع متاحة للحجز حاليًا. يُرجى التواصل مع الصالون مباشرة.</p>
            @elseif($services->isEmpty() || $staffMembers->isEmpty())
                <p class="empty-services">لم تُضف خدمات أو موظفين متاحين للحجز بعد. يُرجى التواصل مع الصالون مباشرة.</p>
            @else
                @php
                    $selectedServiceIds = array_map(
                        'strval',
                        (array) old('service_ids', $selectedServiceId ? [$selectedServiceId] : []),
                    );
                @endphp
                <form class="booking-form" method="POST" action="{{ route('tenant.bookings.store') }}">
                    @csrf
                    <div class="field">
                        <label for="branch_id">الفرع</label>
                        <select id="branch_id" name="branch_id" required>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" @selected((int) $branch->id === (int) old('branch_id', $selectedBranchId))>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                        @error('branch_id')<span class="field-error">{{ $message }}</span>@enderror
                    </div>

                    <fieldset class="field">
                        <legend>الخدمات</legend>
                        <div class="service-options">
                            @foreach($services as $service)
                                <label class="service-option" data-branch="{{ $service->branch_id }}">
                                    <input type="checkbox" name="service_ids[]" value="{{ $service->id }}" @checked(in_array((string) $service->id, $selectedServiceIds, true))>
                                    <span class="service-details">
                                        <strong>{{ $service->name }}</strong>
                                        <small>{{ $service->duration_minutes }} دقيقة · {{ number_format((float) $service->price, 2) }} ج.م</small>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        @error('service_ids')<span class="field-error">{{ $message }}</span>@enderror
                        @error('service_ids.*')<span class="field-error">{{ $message }}</span>@enderror
                    </fieldset>

                    <div class="field">
                        <label for="staff_member_id">الموظف المفضل</label>
                        <select id="staff_member_id" name="staff_member_id" required>
                            <option value="">اختر الموظف</option>
                            @foreach($staffMembers as $staffMember)
                                <option value="{{ $staffMember->id }}" data-branch="{{ $staffMember->branch_id }}" @selected((int) $staffMember->id === (int) old('staff_member_id'))>
                                    {{ $staffMember->name }}{{ $staffMember->job_title ? ' · '.$staffMember->job_title : '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('staff_member_id')<span class="field-error">{{ $message }}</span>@enderror
                    </div>

                    <div class="field">
                        <label for="start_at">الوقت المفضل ({{ $timeZone }})</label>
                        <input id="start_at" name="start_at" type="datetime-local" min="{{ now($timeZone)->format('Y-m-d\TH:i') }}" value="{{ old('start_at') }}" required>
                        @error('start_at')<span class="field-error">{{ $message }}</span>@enderror
                    </div>

                    <div class="field">
                        <label for="contact_name">الاسم</label>
                        <input id="contact_name" name="contact_name" type="text" maxlength="120" autocomplete="name" value="{{ old('contact_name') }}" required>
                        @error('contact_name')<span class="field-error">{{ $message }}</span>@enderror
                    </div>

                    <div class="field">
                        <label for="contact_phone">رقم الهاتف للتواصل</label>
                        <input id="contact_phone" name="contact_phone" type="tel" maxlength="30" autocomplete="tel" value="{{ old('contact_phone') }}" required>
                        @error('contact_phone')<span class="field-error">{{ $message }}</span>@enderror
                    </div>

                    <div class="field">
                        <label for="notes">ملاحظات إضافية (اختياري)</label>
                        <textarea id="notes" name="notes" maxlength="1000">{{ old('notes') }}</textarea>
                        @error('notes')<span class="field-error">{{ $message }}</span>@enderror
                    </div>

                    <button class="button" type="submit">إرسال طلب الموعد</button>
                </form>
            @endif

            <a class="back-link" href="{{ route('tenant.landing') }}">العودة إلى صفحة الصالون</a>
        </section>
    </main>
    <script>
        const bookingBranch = document.getElementById('branch_id');
        const bookingStaff = document.getElementById('staff_member_id');

        if (bookingBranch && bookingStaff) {
            const updateBranchOptions = () => {
                const branchId = bookingBranch.value;

                document.querySelectorAll('.service-option').forEach((option) => {
                    const isAvailable = option.dataset.branch === branchId;
                    option.hidden = !isAvailable;

                    if (!isAvailable) {
                        option.querySelector('input').checked = false;
                    }
                });

                bookingStaff.querySelectorAll('option[data-branch]').forEach((option) => {
                    option.hidden = option.dataset.branch !== branchId;

                    if (option.hidden && option.selected) {
                        bookingStaff.value = '';
                    }
                });
            };

            bookingBranch.addEventListener('change', updateBranchOptions);
            updateBranchOptions();
        }
    </script>
</body>
</html>
