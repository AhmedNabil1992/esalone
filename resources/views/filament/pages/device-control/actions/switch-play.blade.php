                        @php
                            $currentDetail = $this->selectedBusyDevice?->activeSession?->latestDetail;
                            $switchPlayPrices = $this->availableSwitchPlayPrices;
                        @endphp

                        <div style="display:grid;gap:10px;">
                            <div style="font-size:.8rem;color:#4b5563;">
                                نوع اللعب الحالي:
                                <strong>{{ $currentDetail?->play_type ?: '—' }}</strong>
                            </div>

                            <div style="font-size:.78rem;font-weight:700;color:#374151;">اختر نوع اللعب الجديد</div>

                            @if ($switchPlayPrices->isNotEmpty())
                                <div style="display:grid;gap:8px;max-height:240px;overflow:auto;padding-inline-end:2px;">
                                    @foreach ($switchPlayPrices as $price)
                                        @php
                                            $isSelected = (int) $selectedSwitchPlayPriceId === (int) $price->id;
                                        @endphp

                                        <button
                                            type="button"
                                            wire:click="selectSwitchPlayPrice({{ $price->id }})"
                                            style="display:flex;align-items:center;justify-content:space-between;gap:8px;border:1px solid {{ $isSelected ? '#2563eb' : '#d1d5db' }};background:{{ $isSelected ? '#eff6ff' : '#fff' }};border-radius:10px;padding:9px 10px;cursor:pointer;text-align:right;"
                                        >
                                            <span style="display:flex;flex-direction:column;align-items:flex-start;gap:2px;overflow:hidden;">
                                                <span style="font-size:.82rem;font-weight:700;color:#111827;white-space:nowrap;text-overflow:ellipsis;overflow:hidden;max-width:220px;">
                                                    {{ $price->playType?->name ?? '—' }}
                                                </span>
                                                @if ($price->description)
                                                    <span style="font-size:.72rem;color:#6b7280;white-space:nowrap;text-overflow:ellipsis;overflow:hidden;max-width:220px;">
                                                        {{ $price->description }}
                                                    </span>
                                                @endif
                                            </span>

                                            <span style="flex-shrink:0;background:{{ $isSelected ? '#2563eb' : '#f3f4f6' }};color:{{ $isSelected ? '#fff' : '#111827' }};border-radius:8px;padding:3px 8px;font-size:.75rem;font-weight:700;">
                                                {{ number_format((float) $price->price, 2) }}
                                            </span>
                                        </button>
                                    @endforeach
                                </div>

                                <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:2px;">
                                    <button type="button" wire:click="closeBusyActionDetailsModal" style="border:1px solid #d1d5db;background:#fff;color:#111827;padding:8px 12px;border-radius:10px;cursor:pointer;font-size:.8rem;font-weight:700;">
                                        إلغاء
                                    </button>
                                    <button type="button" wire:click="switchBusySessionPlay" style="border:1px solid #2563eb;background:#2563eb;color:#fff;padding:8px 12px;border-radius:10px;cursor:pointer;font-size:.8rem;font-weight:700;">
                                        تنفيذ التبديل
                                    </button>
                                </div>
                            @else
                                <div style="padding:10px;border:1px dashed #d1d5db;border-radius:10px;text-align:center;color:#6b7280;font-size:.8rem;">
                                    لا توجد أنواع لعب أخرى متاحة لهذا الجهاز.
                                </div>
                                <div style="display:flex;justify-content:flex-end;">
                                    <button type="button" wire:click="closeBusyActionDetailsModal" style="border:1px solid #d1d5db;background:#fff;color:#111827;padding:8px 12px;border-radius:10px;cursor:pointer;font-size:.8rem;font-weight:700;">
                                        إغلاق
                                    </button>
                                </div>
                            @endif
                        </div>
