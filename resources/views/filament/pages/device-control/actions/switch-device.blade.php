                        @php
                            $currentBusyDevice = $this->selectedBusyDevice;
                            $currentBusyDetail = $currentBusyDevice?->activeSession?->latestDetail;
                            $switchDevices = $this->availableSwitchDevices;
                        @endphp

                        <div style="display:grid;gap:10px;">
                            <div style="font-size:.8rem;color:#4b5563;line-height:1.6;">
                                الجهاز الحالي:
                                <strong>{{ $currentBusyDevice?->name ?? '—' }}</strong>
                                <br>
                                نوع اللعب الحالي:
                                <strong>{{ $currentBusyDetail?->play_type ?: '—' }}</strong>
                                <br>
                                فئة اللعب:
                                <strong>{{ $currentBusyDetail?->play_category ?: '—' }}</strong>
                            </div>

                            <div style="font-size:.78rem;font-weight:700;color:#374151;">اختر الجهاز الجديد</div>
                            <div style="font-size:.74rem;color:#6b7280;line-height:1.6;">
                                يظهر هنا كل الأجهزة المتاحة.
                                <br>
                                التبديل يتم بنفس نوع اللعب الحالي، وإذا لم يوجد نفس النوع على الجهاز الجديد يتم اختيار أول نوع لعب متاح عليه تلقائياً.
                            </div>

                            @if ($switchDevices->isNotEmpty())
                                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:8px;max-height:280px;overflow:auto;padding-inline-end:2px;">
                                    @foreach ($switchDevices as $candidateDevice)
                                        @php
                                            $isSelectedDevice = (int) $selectedSwitchDeviceId === (int) $candidateDevice->id;
                                            $playTypesText = $candidateDevice->playPrices
                                                ->map(fn ($price) => trim((string) ($price->playType?->name ?? '')))
                                                ->filter(fn (string $name) => $name !== '')
                                                ->unique()
                                                ->values()
                                                ->join(' / ');
                                        @endphp

                                        <button
                                            type="button"
                                            wire:click="selectSwitchDevice({{ $candidateDevice->id }})"
                                            style="border:1px solid {{ $isSelectedDevice ? '#2563eb' : '#d1d5db' }};background:{{ $isSelectedDevice ? '#eff6ff' : '#fff' }};border-radius:12px;padding:8px;display:flex;flex-direction:column;align-items:center;gap:6px;cursor:pointer;text-align:center;"
                                        >
                                            <div style="width:60px;height:60px;border-radius:10px;overflow:hidden;background:#f3f4f6;display:flex;align-items:center;justify-content:center;">
                                                @if ($candidateDevice->type?->picture)
                                                    <img src="{{ tenant_asset($candidateDevice->type->picture) }}" alt="{{ $candidateDevice->name }}" style="width:60px;height:60px;object-fit:contain;display:block;" />
                                                @else
                                                    <svg style="width:24px;height:24px;color:#9ca3af;" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7"/>
                                                    </svg>
                                                @endif
                                            </div>
                                            <div style="font-size:.74rem;font-weight:700;color:#111827;line-height:1.2;word-break:break-word;max-width:100%;">{{ $candidateDevice->name }}</div>
                                            <div style="font-size:.68rem;color:#6b7280;line-height:1.2;word-break:break-word;max-width:100%;">{{ $playTypesText !== '' ? $playTypesText : 'بدون أنواع لعب' }}</div>
                                            <span style="background:{{ $isSelectedDevice ? '#2563eb' : '#f3f4f6' }};color:{{ $isSelectedDevice ? '#fff' : '#111827' }};border-radius:8px;padding:3px 8px;font-size:.72rem;font-weight:700;">
                                                {{ $isSelectedDevice ? 'مختار' : 'متاح' }}
                                            </span>
                                        </button>
                                    @endforeach
                                </div>

                                <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:2px;">
                                    <button type="button" wire:click="closeBusyActionDetailsModal" style="border:1px solid #d1d5db;background:#fff;color:#111827;padding:8px 12px;border-radius:10px;cursor:pointer;font-size:.8rem;font-weight:700;">
                                        إلغاء
                                    </button>
                                    <button type="button" wire:click="switchBusySessionDevice" style="border:1px solid #2563eb;background:#2563eb;color:#fff;padding:8px 12px;border-radius:10px;cursor:pointer;font-size:.8rem;font-weight:700;">
                                        تنفيذ التبديل
                                    </button>
                                </div>
                            @else
                                <div style="padding:10px;border:1px dashed #d1d5db;border-radius:10px;text-align:center;color:#6b7280;font-size:.8rem;">
                                    لا توجد أجهزة متاحة للتبديل الآن.
                                </div>
                                <div style="display:flex;justify-content:flex-end;">
                                    <button type="button" wire:click="closeBusyActionDetailsModal" style="border:1px solid #d1d5db;background:#fff;color:#111827;padding:8px 12px;border-radius:10px;cursor:pointer;font-size:.8rem;font-weight:700;">
                                        إغلاق
                                    </button>
                                </div>
                            @endif
                        </div>
