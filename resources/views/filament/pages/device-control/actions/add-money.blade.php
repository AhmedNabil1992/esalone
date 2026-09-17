                        @php
                            $currentPrepaid = (float) ($this->selectedBusyDevice?->activeSession?->prepaid_amount ?? 0);
                        @endphp

                        <div style="font-size:.82rem;color:#4b5563;margin-bottom:10px;">
                            الرصيد الحالي المقدم: <strong>{{ number_format($currentPrepaid, 2) }}</strong>
                        </div>

                        <input
                            type="number"
                            min="0.01"
                            max="999"
                            step="0.01"
                            wire:model.live="busyActionAmount"
                            placeholder="اكتب المبلغ المطلوب إضافته"
                            style="width:100%;border-radius:10px;padding:8px 10px;font-size:.85rem;margin-bottom:12px;"
                            class="bg-white dark:!bg-zinc-950 border border-slate-300 dark:!border-zinc-700 text-slate-900 dark:!text-zinc-100 placeholder-slate-400 dark:!placeholder-zinc-500"
                        />

                        <div style="display:flex;justify-content:flex-end;gap:8px;">
                            <button type="button" wire:click="closeBusyActionDetailsModal" style="border:1px solid #d1d5db;background:#fff;color:#111827;padding:8px 12px;border-radius:10px;cursor:pointer;font-size:.8rem;font-weight:700;">
                                إلغاء
                            </button>
                            <button type="button" wire:click="addBusySessionAmount" style="border:1px solid #16a34a;background:#16a34a;color:#fff;padding:8px 12px;border-radius:10px;cursor:pointer;font-size:.8rem;font-weight:700;">
                                إضافة المبلغ
                            </button>
                        </div>
