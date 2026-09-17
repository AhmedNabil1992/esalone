        <div class="device-control-type-tabs" style="display:flex;gap:10px;overflow-x:auto;padding-bottom:4px;border-bottom:2px solid #e5e7eb;">
            @forelse ($this->deviceTypes as $type)
                <button
                    wire:click="selectType({{ $type->id }})"
                    type="button"
                    style="
                        flex-shrink:0;
                        display:flex;
                        flex-direction:column;
                        align-items:center;
                        gap:8px;
                        padding:10px 16px;
                        border-radius:14px;
                        border:2px solid {{ $selectedTypeId === $type->id ? '#6366f1' : '#e5e7eb' }};
                        background:{{ $selectedTypeId === $type->id ? '#eef2ff' : '#ffffff' }};
                        box-shadow:{{ $selectedTypeId === $type->id ? '0 0 0 3px #c7d2fe' : 'none' }};
                        cursor:pointer;
                        transition:all .15s;
                        min-width:90px;
                    "
                >
                    <span style="width:52px;height:52px;border-radius:10px;overflow:hidden;display:flex;align-items:center;justify-content:center;background:#f3f4f6;flex-shrink:0;">
                        @if ($type->picture)
                            <img src="{{ tenant_asset($type->picture) }}" alt="{{ $type->name }}"
                                 style="width:52px;height:52px;object-fit:contain;display:block;" />
                        @else
                            <svg style="width:28px;height:28px;color:#9ca3af;" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7"/>
                            </svg>
                        @endif
                    </span>
                    <span style="font-size:.75rem;font-weight:700;color:{{ $selectedTypeId === $type->id ? '#4338ca' : '#374151' }};text-align:center;line-height:1.3;max-width:80px;word-break:break-word;">
                        {{ $type->name }}
                    </span>
                </button>
            @empty
                <p style="font-size:.875rem;color:#9ca3af;padding:8px 0;">لا توجد أنواع أجهزة مفعّلة.</p>
            @endforelse
        </div>

