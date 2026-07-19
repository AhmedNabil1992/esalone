<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-6">
        
        @if(!$hasAiSubscription)
            <div style="background: linear-gradient(135deg, #fef3c7, #fffbeb); border: 1px solid #fde68a; border-radius: 16px; padding: 18px 24px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); display: flex; align-items: center; gap: 15px;">
                <div style="background: #f59e0b; color: #fff; width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; box-shadow: 0 4px 10px rgba(245, 158, 11, 0.3);">
                    <x-heroicon-o-lock-closed class="w-6 h-6" />
                </div>
                <div>
                    <h4 style="font-weight: 800; color: #92400e; font-size: 0.95rem; margin: 0;">باقة المساعد الذكي (AI Assistant) غير مفعلة</h4>
                    <p style="font-size: 0.8rem; color: #b45309; margin: 4px 0 0 0; font-weight: 600;">عفواً، هذا الفرع غير مشترك في خدمة الذكاء الاصطناعي. يرجى التواصل مع إدارة النظام أو الدعم الفني لتفعيل الاشتراك وزيادة إنتاجية صالتك!</p>
                </div>
            </div>
        @endif
        
        <!-- Usage Card -->
        <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 16px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="background: rgba(59, 130, 246, 0.1); color: #3b82f6; width: 42px; height: 42px; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                    <x-heroicon-o-chart-bar class="w-6 h-6" />
                </div>
                <div>
                    <h4 style="font-weight: 800; color: #1e293b; font-size: 0.95rem;">معدل استهلاك الذكاء الاصطناعي اليومي</h4>
                    <p style="font-size: 0.75rem; color: #64748b;">يتم تصفير العداد تلقائياً كل 24 ساعة.</p>
                </div>
            </div>
            
            <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 4px;">
                <span style="font-weight: bold; color: #1e293b; font-size: 0.9rem;">
                    {{ $requestsToday }} / {{ $requestsLimit }} سؤال مستخدم اليوم
                </span>
                <div style="width: 200px; height: 8px; background: #f1f5f9; border-radius: 99px; overflow: hidden; border: 1px solid #e2e8f0;">
                    @php
                        $percentage = min(100, max(0, ($requestsToday / max(1, $requestsLimit)) * 100));
                        $barColor = $percentage > 85 ? '#ef4444' : ($percentage > 60 ? '#f59e0b' : '#3b82f6');
                    @endphp
                    <div style="width: {{ $percentage }}%; height: 100%; background: {{ $barColor }}; border-radius: 99px; transition: width 0.3s ease;"></div>
                </div>
            </div>
        </div>

        <!-- Chat Main Container -->
        <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 20px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05); display: flex; flex-direction: column; height: 750px; overflow: hidden;">
            
            <!-- Chat Header -->
            <div style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 16px 20px; display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="background: linear-gradient(135deg, #3b82f6, #8b5cf6); color: #fff; width: 38px; height: 38px; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 6px rgba(59, 130, 246, 0.25);">
                        <x-heroicon-o-sparkles class="w-5 h-5" />
                    </div>
                    <div>
                        <h3 style="font-weight: 800; color: #0f172a; font-size: 0.95rem;">المساعد الذكي (AI Assistant)</h3>
                        <div style="display: flex; align-items: center; gap: 5px;">
                            <span style="width: 8px; height: 8px; border-radius: 50%; background: #22c55e; display: inline-block;"></span>
                            <span style="font-size: 0.72rem; color: #64748b; font-weight: 600;">نشط ومتصل</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Chat Messages Scroll Area -->
            <div id="chat-messages-container" style="flex: 1; overflow-y: auto; padding: 20px; display: flex; flex-direction: column; gap: 15px; background: #fafafb;">
                @foreach($messages as $msg)
                    @if($msg['sender'] === 'ai')
                        @php $isPending = ($msg['type'] ?? '') === 'pending_confirmation'; @endphp
                        <!-- AI Message -->
                        <div style="display: flex; gap: 12px; max-width: 85%;">
                            <div style="background: linear-gradient(135deg, #3b82f6, #8b5cf6); color: #fff; width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; box-shadow: 0 4px 6px rgba(59,130,246,0.15);">
                                <x-heroicon-o-sparkles class="w-4 h-4" />
                            </div>
                            <div style="background: {{ $isPending ? '#fffbeb' : '#fff' }}; border: 1.5px solid {{ $isPending ? '#fbbf24' : '#e2e8f0' }}; border-radius: 0 16px 16px 16px; padding: 14px 18px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); display: flex; flex-direction: column; gap: 8px;">
                                <div style="font-weight: 700; color: {{ $isPending ? '#b45309' : '#3b82f6' }}; font-size: 0.75rem; margin-bottom: 2px;">المساعد الذكي{{ $isPending ? ' — يحتاج تأكيداً ⚠️' : '' }}</div>
                                <div style="font-size: 0.9rem; color: #334155; line-height: 1.6; white-space: pre-wrap; font-weight: 500;">{!! $msg['text'] !!}</div>
                                @if($isPending)
                                <div style="display: flex; gap: 8px; margin-top: 6px; flex-wrap: wrap;">
                                    <button wire:click="confirmPendingAction" style="background: linear-gradient(135deg, #22c55e, #16a34a); color: #fff; border: none; border-radius: 8px; padding: 8px 18px; font-size: 0.85rem; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 6px;">
                                        ✅ تأكيد التعديل
                                    </button>
                                    <button wire:click="cancelPendingAction" style="background: linear-gradient(135deg, #ef4444, #dc2626); color: #fff; border: none; border-radius: 8px; padding: 8px 18px; font-size: 0.85rem; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 6px;">
                                        ❌ إلغاء
                                    </button>
                                </div>
                                @endif
                            </div>
                        </div>
                    @else
                        <!-- User Message -->
                        <div style="display: flex; gap: 12px; max-width: 85%; align-self: flex-end; flex-direction: row-reverse;">
                            <div style="background: #64748b; color: #fff; width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <x-heroicon-o-user class="w-4 h-4" />
                            </div>
                            <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 16px 0 16px 16px; padding: 14px 18px; box-shadow: 0 2px 4px rgba(59,130,246,0.02); display: flex; flex-direction: column; gap: 4px;">
                                <div style="font-weight: 700; color: #1d4ed8; font-size: 0.75rem; margin-bottom: 2px; text-align: left;">أنت (المدير)</div>
                                <div style="font-size: 0.9rem; color: #1e3a8a; line-height: 1.6; white-space: pre-wrap; font-weight: 600;">{{ $msg['text'] }}</div>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>

            <!-- Chat Input Footer -->
            <div style="background: #fff; border-top: 1px solid #e2e8f0; padding: 15px 20px;">
                @if($pendingAction)
                <!-- Pending confirmation banner -->
                <div style="background: linear-gradient(135deg, #fef3c7, #fffbeb); border: 1.5px solid #fbbf24; border-radius: 12px; padding: 12px 16px; margin-bottom: 12px; display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="font-size: 1.1rem;">⚠️</span>
                        <span style="font-size: 0.85rem; font-weight: 700; color: #92400e;">في انتظار تأكيدك على تعديل الأسعار المقترح</span>
                    </div>
                    <div style="display: flex; gap: 8px;">
                        <button wire:click="confirmPendingAction" type="button" style="background: #22c55e; color: #fff; border: none; border-radius: 8px; padding: 7px 16px; font-size: 0.82rem; font-weight: 700; cursor: pointer;">✅ تأكيد</button>
                        <button wire:click="cancelPendingAction" type="button" style="background: #ef4444; color: #fff; border: none; border-radius: 8px; padding: 7px 16px; font-size: 0.82rem; font-weight: 700; cursor: pointer;">❌ إلغاء</button>
                    </div>
                </div>
                @endif
                <form wire:submit.prevent="sendMessage" style="display: flex; gap: 10px; align-items: center;">
                    <div style="flex: 1; position: relative;">
                        <input 
                            type="text" 
                            wire:model="question" 
                            placeholder="{{ $hasAiSubscription ? 'اسألني عن إيرادات اليوم، الوردية الحالية، مبيعات الكافيه...' : 'برجاء تفعيل اشتراك الذكاء الاصطناعي أولاً للاستخدام...' }}" 
                            style="width: 100%; border: 1px solid #cbd5e1; border-radius: 99px; padding: 12px 24px; font-size: 0.9rem; font-weight: 600; color: #334155; background: {{ $hasAiSubscription ? '#fff' : '#f1f5f9' }};"
                            required
                            @disabled(!$hasAiSubscription)
                        />
                    </div>
                    <button 
                        type="submit" 
                        wire:loading.attr="disabled"
                        @disabled(!$hasAiSubscription)
                        style="background: {{ $hasAiSubscription ? 'linear-gradient(135deg, #3b82f6, #2563eb)' : '#94a3b8' }}; color: #fff; border-radius: 99px; width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; border: none; cursor: {{ $hasAiSubscription ? 'pointer' : 'not-allowed' }}; flex-shrink: 0; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05); transition: all 0.2s;"
                    >
                        <x-heroicon-o-paper-airplane wire:loading.remove class="w-5 h-5 transform rotate-180" />
                        <svg wire:loading class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </button>
                </form>
            </div>
        </div>

    </div>

    <!-- Scroll Auto script -->
    <script>
        document.addEventListener('livewire:init', () => {
            const container = document.getElementById('chat-messages-container');
            if (container) {
                container.scrollTop = container.scrollHeight;
            }

            Livewire.on('scroll-to-bottom', () => {
                setTimeout(() => {
                    if (container) {
                        container.scrollTo({
                            top: container.scrollHeight,
                            behavior: 'smooth'
                        });
                    }
                }, 50);
            });
        });
    </script>
</x-filament-panels::page>
