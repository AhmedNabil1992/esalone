<!-- SQLite WASM for Offline Database -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/sql.js/1.8.0/sql-wasm.js"></script>

<style>
    body.is-offline .fi-main, 
    body.is-offline .fi-topbar {
        display: none !important;
    }
    body.is-offline .fi-layout {
        display: flex;
        flex-direction: row;
    }
    body.is-offline #offline-root {
        display: block !important;
        flex: 1;
        min-height: 100vh;
        background-color: #f9fafb;
        padding: 24px;
        direction: rtl;
        font-family: 'Cairo', sans-serif;
    }
</style>

<div id="offline-wrapper" x-data="globalOfflineManager()" x-init="initGlobal()">
    <!-- Sync Progress Overlay -->
    <div x-show="isSyncingState" class="fixed inset-0 z-[110] bg-gray-900 bg-opacity-70 flex flex-col items-center justify-center text-center p-6" style="display: none; direction: rtl; font-family: 'Cairo', sans-serif;">
        <div class="bg-white rounded-2xl border border-gray-200 shadow-2xl p-8 max-w-sm w-full flex flex-col gap-4 items-center">
            <div class="relative w-20 h-20 flex items-center justify-center">
                <div class="absolute inset-0 rounded-full border-4 border-gray-100"></div>
                <div class="absolute inset-0 rounded-full border-4 border-t-emerald-600 animate-spin"></div>
                <span class="text-xs font-bold text-gray-700" x-text="syncPercent + '%'">0%</span>
            </div>
            <h3 class="text-sm font-bold text-gray-900" x-text="syncStepName">جاري تهيئة قاعدة البيانات...</h3>
            <div class="w-full bg-gray-100 rounded-full h-2">
                <div class="bg-emerald-600 h-2 rounded-full transition-all duration-300" :style="'width: ' + syncPercent + '%'"></div>
            </div>
            <p class="text-[10px] text-gray-400">يرجى عدم إغلاق الصفحة لحين اكتمال المزامنة.</p>
        </div>
    </div>

    <div id="offline-root" style="display: none;">
    <!-- Security Lockout Overlay -->
    <div x-show="isLocked" class="fixed inset-0 z-[100] bg-gray-900 bg-opacity-95 flex flex-col items-center justify-center text-center p-6 select-none" style="display: none;">
        <div class="bg-white rounded-2xl border border-gray-200 shadow-2xl p-8 max-w-md flex flex-col gap-6 items-center">
            <span class="text-6xl text-red-600 animate-bounce">⚠️</span>
            <h2 class="text-xl font-bold text-gray-900">تنبيه أمان - تم قفل النظام</h2>
            <p class="text-sm text-gray-600 font-bold" x-text="lockReason"></p>
            <div class="w-full h-1 bg-red-100 rounded-full overflow-hidden">
                <div class="h-full bg-red-600 animate-pulse w-full"></div>
            </div>
            <p class="text-[10px] text-gray-400">يرجى توصيل كابل الإنترنت أو شبكة الـ Wi-Fi للتحقق ومزامنة البيانات.</p>
        </div>
    </div>

    <!-- Offline Dashboard Wrapper -->
    <div class="flex flex-col gap-6">
        
        <!-- PWA Status Header -->
        <div class="flex flex-wrap items-center justify-between gap-4 p-5 bg-white rounded-xl border border-gray-200 shadow-sm">
            <div class="flex items-center gap-3">
                <span class="text-2xl">⚡</span>
                <div>
                    <h2 class="text-lg font-bold text-gray-900" x-text="getPageTitle()"></h2>
                    <p class="text-xs text-gray-500">ePlayStation Cashier Desk - وضع العمل دون اتصال بالإنترنت</p>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-semibold bg-red-50 text-red-700 border border-red-200">
                    <span class="w-2.5 h-2.5 rounded-full bg-red-500 animate-pulse"></span>
                    <span>يعمل أوفلاين</span>
                </div>
                <button @click="fetchInitialCloudState()" :disabled="!isOnline"
                        class="flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white rounded-lg text-xs font-bold transition-all shadow-sm">
                    <span>🔄 تهيئة ومزامنة البيانات الأساسية</span>
                </button>
                <button @click="syncQueue()" :disabled="syncing || queueCount === 0" 
                        class="flex items-center gap-2 px-4 py-2 bg-violet-600 hover:bg-violet-700 disabled:opacity-50 text-white rounded-lg text-xs font-bold transition-all shadow-sm">
                    <span x-show="syncing" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                    <span>مزامنة العمليات المعلقة (<span x-text="queueCount">0</span>)</span>
                </button>
            </div>
        </div>

        <!-- Render Target View based on Route -->
        <div>
            <!-- VIEW 1: DEVICE CONTROL -->
            <div x-show="currentRoute === 'device-control'">
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    <template x-for="device in devices" :key="device.id">
                        <div class="p-5 bg-white rounded-xl border border-gray-200 shadow-sm flex flex-col justify-between transition-all hover:shadow-md"
                             :class="device.status === 'busy' ? 'border-r-4 border-r-amber-500' : 'border-r-4 border-r-green-500'">
                            <div>
                                <div class="flex items-center justify-between mb-3">
                                    <h3 class="font-bold text-gray-900" x-text="device.name"></h3>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold"
                                          :class="device.status === 'busy' ? 'bg-amber-50 text-amber-700' : 'bg-green-50 text-green-700'"
                                          x-text="device.status === 'busy' ? 'مشغول' : 'متاح'"></span>
                                </div>
                                <p class="text-xs text-gray-500 mb-2" x-text="device.type ? device.type.name : 'جهاز PS'"></p>
                                
                                <div x-show="device.status === 'busy'" class="bg-gray-50 p-3 rounded-lg text-xs flex flex-col gap-1.5 my-3">
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">مدة اللعب:</span>
                                        <span class="font-bold text-gray-900" x-text="formatDuration(device.elapsedMinutes)"></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">تكلفة اللعب التقريبية:</span>
                                        <span class="font-bold text-violet-700" x-text="device.accumulatedCost ? device.accumulatedCost + ' ج.م' : '0 ج.م'"></span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-4 flex gap-2">
                                <button x-show="device.status !== 'busy'" @click="openStartModal(device)" class="w-full py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-xs font-bold transition-all">تشغيل الجهاز</button>
                                <button x-show="device.status === 'busy'" @click="openCloseModal(device)" class="w-full py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs font-bold transition-all">إنهاء وحساب</button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- VIEW 2: DIRECT SALES / POS -->
            <div x-show="currentRoute === 'sales'" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm flex flex-col justify-between h-[600px]">
                    <div>
                        <h3 class="font-bold text-gray-900 border-b pb-3 mb-4 flex items-center gap-2">🛍️ سلة المشتريات</h3>
                        <div class="overflow-y-auto max-h-[350px] flex flex-col gap-3">
                            <template x-for="item in cart" :key="item.id">
                                <div class="flex items-center justify-between bg-gray-50 p-2.5 rounded-lg text-xs">
                                    <div>
                                        <div class="font-bold text-gray-900" x-text="item.name"></div>
                                        <div class="text-gray-500 mt-1" x-text="item.sale_price + ' ج.م'"></div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button @click="updateCartQty(item.id, -1)" class="w-6 h-6 bg-white border rounded flex items-center justify-center font-bold text-gray-700 hover:bg-gray-100">-</button>
                                        <span class="font-bold text-gray-900" x-text="item.qty"></span>
                                        <button @click="updateCartQty(item.id, 1)" class="w-6 h-6 bg-white border rounded flex items-center justify-center font-bold text-gray-700 hover:bg-gray-100">+</button>
                                    </div>
                                </div>
                            </template>
                            <template x-if="cart.length === 0">
                                <div class="text-center text-gray-400 py-10">السلة فارغة. اختر صنفاً.</div>
                            </template>
                        </div>
                    </div>
                    <div class="border-t pt-4 flex flex-col gap-3">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500 font-medium">إجمالي السعر:</span>
                            <span class="font-bold text-gray-950" x-text="cartTotal + ' ج.م'"></span>
                        </div>
                        <div class="flex gap-2">
                            <select x-model="posCustomerId" class="w-1/2 text-xs border rounded-lg p-2.5 bg-white">
                                <option value="">بدون عميل (كاشير)</option>
                                <template x-for="c in customers" :key="c.id">
                                    <option :value="c.id" x-text="c.name"></option>
                                </template>
                            </select>
                            <select x-model="posCashBoxId" class="w-1/2 text-xs border rounded-lg p-2.5 bg-white">
                                <template x-for="cb in cashBoxes" :key="cb.id">
                                    <option :value="cb.id" x-text="cb.name"></option>
                                </template>
                            </select>
                        </div>
                        <button @click="checkoutPOS()" :disabled="cart.length === 0" class="w-full py-3 bg-violet-600 hover:bg-violet-700 disabled:opacity-50 text-white rounded-lg font-bold text-xs shadow-sm transition-all">إتمام البيع</button>
                    </div>
                </div>

                <div class="lg:col-span-2 bg-white p-5 rounded-xl border border-gray-200 shadow-sm flex flex-col gap-4">
                    <div class="flex justify-between items-center gap-4 flex-wrap">
                        <h3 class="font-bold text-gray-900">🍔 قائمة الأصناف والمأكولات</h3>
                        <input type="text" x-model="searchProduct" placeholder="ابحث باسم المنتج..." class="text-xs border rounded-lg p-2.5 w-64 bg-white" />
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3 overflow-y-auto max-h-[480px] p-1">
                        <template x-for="item in filteredCatalog" :key="item.id">
                            <div @click="addToCart(item)" class="p-3.5 bg-gray-50 hover:bg-violet-50 hover:border-violet-300 border border-gray-200 rounded-lg cursor-pointer transition-all flex flex-col justify-between h-28">
                                <div class="font-bold text-xs text-gray-900" x-text="item.name"></div>
                                <div class="flex justify-between items-center mt-2">
                                    <span class="text-xs font-bold text-violet-700" x-text="item.sale_price + ' ج.م'"></span>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- VIEW 3: WAITING LIST -->
            <div x-show="currentRoute === 'waiting-lists'" class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm flex flex-col gap-6">
                <div class="flex justify-between items-center gap-4 flex-wrap border-b pb-4">
                    <h3 class="font-bold text-gray-900">⏳ قائمة الانتظار الحالية</h3>
                    <div class="flex gap-2">
                        <input type="text" x-model="queueName" placeholder="اسم العميل..." class="text-xs border rounded-lg p-2.5 w-48 bg-white" />
                        <input type="text" x-model="queueNotes" placeholder="ملاحظات..." class="text-xs border rounded-lg p-2.5 w-48 bg-white" />
                        <button @click="addToQueue()" class="px-4 py-2 bg-violet-600 hover:bg-violet-700 text-white rounded-lg text-xs font-bold transition-all shadow-sm">إضافة للانتظار</button>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-right border-collapse text-xs">
                        <thead>
                            <tr class="bg-gray-50 text-gray-500 border-b">
                                <th class="p-3 font-bold">اسم العميل</th>
                                <th class="p-3 font-bold text-center">وقت الدخول للانتظار</th>
                                <th class="p-3 font-bold">ملاحظات</th>
                                <th class="p-3 font-bold text-center">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="q in queueList" :key="q.id">
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="p-3 font-bold text-gray-900" x-text="q.customer_name"></td>
                                    <td class="p-3 text-center text-gray-600" x-text="formatTime(q.time)"></td>
                                    <td class="p-3 text-gray-500" x-text="q.notes || '-'"></td>
                                    <td class="p-3 text-center">
                                        <button @click="deleteFromQueue(q.id)" class="px-3 py-1 bg-red-50 text-red-600 hover:bg-red-100 rounded text-[11px] font-bold">حذف</button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- VIEW 4: RESERVATIONS -->
            <div x-show="currentRoute === 'reservations' || currentRoute === 'reservations-calendar'" class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm flex flex-col gap-6">
                <div class="flex justify-between items-center gap-4 flex-wrap border-b pb-4">
                    <h3 class="font-bold text-gray-900">📅 حجوزات اليوم</h3>
                    <div class="flex gap-2 items-center flex-wrap">
                        <input type="text" x-model="resName" placeholder="اسم العميل..." class="text-xs border rounded-lg p-2.5 w-40 bg-white" />
                        <input type="time" x-model="resTime" class="text-xs border rounded-lg p-2.5 w-32 bg-white" />
                        <select x-model="resDeviceId" class="text-xs border rounded-lg p-2.5 bg-white w-40">
                            <option value="">اختر الجهاز...</option>
                            <template x-for="d in devices" :key="d.id">
                                <option :value="d.id" x-text="d.name"></option>
                            </template>
                        </select>
                        <button @click="addReservation()" class="px-4 py-2 bg-violet-600 hover:bg-violet-700 text-white rounded-lg text-xs font-bold transition-all shadow-sm">إضافة حجز</button>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-right border-collapse text-xs">
                        <thead>
                            <tr class="bg-gray-50 text-gray-500 border-b">
                                <th class="p-3 font-bold">اسم العميل</th>
                                <th class="p-3 font-bold text-center">وقت الحجز</th>
                                <th class="p-3 font-bold">الجهاز</th>
                                <th class="p-3 font-bold text-center">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="res in reservationList" :key="res.id">
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="p-3 font-bold text-gray-900" x-text="res.customer_name"></td>
                                    <td class="p-3 text-center text-gray-600" x-text="res.time"></td>
                                    <td class="p-3 text-gray-700" x-text="getDeviceName(res.device_id)"></td>
                                    <td class="p-3 text-center">
                                        <button @click="deleteReservation(res.id)" class="px-3 py-1 bg-red-50 text-red-600 hover:bg-red-100 rounded text-[11px] font-bold">حذف</button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- VIEW 5: PLAY SESSIONS -->
            <div x-show="currentRoute === 'play-sessions'" class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm flex flex-col gap-4">
                <h3 class="font-bold text-gray-900 border-b pb-3">🎮 جلسات اللعب الجارية</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <template x-for="session in activeSessionsList" :key="session.id">
                        <div class="p-3.5 bg-gray-50 border rounded-lg flex flex-col gap-2 text-xs">
                            <div class="flex justify-between items-center">
                                <span class="font-bold text-gray-900" x-text="'كود الجلسة: ' + (session.session_key || 'مؤقتة')"></span>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-700">نشطة حالياً</span>
                            </div>
                            <div class="flex justify-between text-gray-500">
                                <span>الجهاز:</span>
                                <span class="font-bold text-gray-700" x-text="getDeviceName(session.device_id)"></span>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

        </div>
    </div>

    <!-- MODAL 1: START DEVICE SESSION -->
    <div x-show="showStartModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4">
        <div class="bg-white rounded-xl shadow-lg border w-full max-w-md p-6 flex flex-col gap-4">
            <div class="flex justify-between items-center border-b pb-3">
                <h3 class="font-bold text-gray-900">تشغيل الجهاز: <span x-text="targetDevice?.name"></span></h3>
                <button @click="showStartModal = false" class="text-gray-400 hover:text-gray-600 text-lg">&times;</button>
            </div>
            <div class="flex flex-col gap-1">
                <label class="text-xs font-bold text-gray-700">سعر اللعب:</label>
                <select x-model="startPlayPriceId" class="text-xs border rounded-lg p-2.5 bg-white">
                    <option value="">اختر السعر...</option>
                    <template x-for="p in playPrices" :key="p.id">
                        <option :value="p.id" x-text="(p.play_type ? p.play_type.name : '') + ' - ' + p.price + ' ج.م/ساعة'"></option>
                    </template>
                </select>
            </div>
            <div class="flex flex-col gap-1">
                <label class="text-xs font-bold text-gray-700">نوع التشغيل:</label>
                <select x-model="startType" class="text-xs border rounded-lg p-2.5 bg-white">
                    <option value="open">مفتوح (Unlimited)</option>
                    <option value="limit">محدد بالوقت (Limited)</option>
                </select>
            </div>
            <div x-show="startType === 'limit'" class="flex flex-col gap-1">
                <label class="text-xs font-bold text-gray-700">عدد الدقائق:</label>
                <input type="number" x-model="startDuration" class="text-xs border rounded-lg p-2.5 bg-white" />
            </div>
            <div class="flex justify-end gap-2 border-t pt-4">
                <button @click="showStartModal = false" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-xs font-bold transition-all">إلغاء</button>
                <button @click="confirmStartSession()" :disabled="!startPlayPriceId" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-xs font-bold transition-all disabled:opacity-50">تشغيل</button>
            </div>
        </div>
    </div>

    <!-- MODAL 2: CLOSE/CHECKOUT DEVICE SESSION -->
    <div x-show="showCloseModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4">
        <div class="bg-white rounded-xl shadow-lg border w-full max-w-md p-6 flex flex-col gap-4">
            <div class="flex justify-between items-center border-b pb-3">
                <h3 class="font-bold text-gray-900">إنهاء حساب الجهاز: <span x-text="targetDevice?.name"></span></h3>
                <button @click="showCloseModal = false" class="text-gray-400 hover:text-gray-600 text-lg">&times;</button>
            </div>
            <div class="bg-gray-50 p-4 rounded-xl flex flex-col gap-2.5 text-xs">
                <div class="flex justify-between">
                    <span class="text-gray-500">مدة اللعب:</span>
                    <span class="font-bold text-gray-900" x-text="formatDuration(targetDevice?.elapsedMinutes)"></span>
                </div>
                <div class="flex justify-between text-sm pt-1.5 border-t">
                    <span class="text-gray-500 font-bold">إجمالي تكلفة اللعب:</span>
                    <span class="font-bold text-violet-700" x-text="targetDevice?.accumulatedCost + ' ج.م'"></span>
                </div>
            </div>
            <div class="flex flex-col gap-1">
                <label class="text-xs font-bold text-gray-700">المبلغ المدفوع:</label>
                <input type="number" x-model="closePaidAmount" class="text-xs border rounded-lg p-2.5 bg-white" />
            </div>
            <div class="flex justify-end gap-2 border-t pt-4">
                <button @click="showCloseModal = false" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-xs font-bold transition-all">إلغاء</button>
                <button @click="confirmCloseSession()" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs font-bold transition-all">إغلاق الجلسة وتحصيل الحساب</button>
            </div>
        </div>
</div>
</div>

<script>
    function globalOfflineManager() {
        return {
            db: null,
            sqlDb: null,
            isOnline: navigator.onLine,
            currentRoute: '',
            queueCount: 0,
            syncing: false,
            
            // Sync status feedback
            isSyncingState: false,
            syncPercent: 0,
            syncStepName: '',
            
            // Security Lockout
            isLocked: false,
            lockReason: '',
            lastSyncTime: null,
            subscriptionExpiry: null,

            // Shared Data
            devices: [],
            catalogItems: [],
            customers: [],
            cashBoxes: [],
            queueList: [],
            reservationList: [],
            activeSessionsList: [],
            playSessionDetailsList: [],

            // Modals
            showStartModal: false,
            showCloseModal: false,
            targetDevice: null,
            playPrices: [],
            startPlayPriceId: '',
            startType: 'open',
            startDuration: '',
            closePaidAmount: 0,

            // POS
            cart: [],
            cartTotal: 0,
            searchProduct: '',
            posCustomerId: '',
            posCashBoxId: '',

            // Queue
            queueName: '',
            queueNotes: '',

            // Reservations
            resName: '',
            resTime: '',
            resDeviceId: '',

            async initGlobal() {
                window.globalOfflineManagerInstance = this;
                // Register Service Worker
                if ('serviceWorker' in navigator) {
                    navigator.serviceWorker.register('/sw.js');
                }

                // Detect Route
                this.resolveRoute();

                // SQLite WASM Init
                const success = await this.initSQLite();
                if (success) {
                    await this.loadLocalData();
                    if (this.isOnline) {
                        await this.fetchInitialCloudState();
                    }
                }

                // Connection Listeners
                window.addEventListener('online', () => {
                    this.isOnline = true;
                    document.body.classList.remove('is-offline');
                    this.syncQueue();
                });
                window.addEventListener('offline', () => {
                    this.isOnline = false;
                    document.body.classList.add('is-offline');
                });

                // Set initial status class
                if (!this.isOnline) {
                    document.body.classList.add('is-offline');
                }

                // Intercept clicks when offline OR when triggering offline sync
                window.addEventListener('click', (e) => {
                    const trigger = e.target.closest('.offline-sync-trigger');
                    if (trigger) {
                        e.preventDefault();
                        e.stopPropagation();
                        // Close User menu dropdown if open (simulated by dispatching escape key or blur)
                        document.activeElement?.blur();
                        this.fetchInitialCloudState();
                        return;
                    }

                    if (!this.isOnline) {
                        const anchor = e.target.closest('a');
                        if (anchor) {
                            const href = anchor.getAttribute('href');
                            if (href && (href.startsWith('/') || href.includes(window.location.host))) {
                                // Map path to route
                                let matchedRoute = null;
                                if (href.includes('/device-control')) matchedRoute = 'device-control';
                                else if (href.includes('/sales')) matchedRoute = 'sales';
                                else if (href.includes('/waiting-lists')) matchedRoute = 'waiting-lists';
                                else if (href.includes('/reservations')) matchedRoute = 'reservations';
                                else if (href.includes('/play-sessions')) matchedRoute = 'play-sessions';
                                
                                if (matchedRoute) {
                                    e.preventDefault();
                                    e.stopPropagation();
                                    this.currentRoute = matchedRoute;
                                    history.pushState(null, '', href);
                                    console.log("Offline intercepted route to:", matchedRoute);
                                }
                            }
                        }
                    }
                }, true);

                // Listen to popstate (browser back/forward)
                window.addEventListener('popstate', () => {
                    this.resolveRoute();
                });

                // Heartbeat check to detect server status (e.g. Herd stopped)
                const checkServerStatus = async () => {
                    try {
                        const res = await fetch('/tenant/offline/init', { method: 'HEAD', cache: 'no-store' });
                        if (res.ok) {
                            if (!this.isOnline) {
                                this.isOnline = true;
                                document.body.classList.remove('is-offline');
                                this.syncQueue();
                            }
                        } else {
                            throw new Error("Server returned error");
                        }
                    } catch (e) {
                        if (this.isOnline) {
                            this.isOnline = false;
                            document.body.classList.add('is-offline');
                            console.log("Server unreachable. Switched to offline mode.");
                        }
                    }
                };

                // Run immediately and then every 8 seconds
                checkServerStatus();
                setInterval(checkServerStatus, 8000);

                // Dynamic ticking loops
                setInterval(() => {
                    this.tickActiveDevices();
                }, 1000);
            },

            async initSQLite() {
                try {
                    const config = {
                        locateFile: filename => `https://cdnjs.cloudflare.com/ajax/libs/sql.js/1.8.0/${filename}`
                    };
                    const SQL = await initSqlJs(config);
                    
                    const savedDb = await this.loadDatabaseFromIndexedDB();
                    if (savedDb) {
                        this.sqlDb = new SQL.Database(new Uint8Array(savedDb));
                    } else {
                        this.sqlDb = new SQL.Database();
                    }
                    console.log("SQLite WASM Database initialized successfully.");
                    return true;
                } catch (e) {
                    console.error("Failed to initialize SQLite WASM", e);
                    return false;
                }
            },

            async loadDatabaseFromIndexedDB() {
                return new Promise((resolve) => {
                    const request = window.indexedDB.open("ePlayStationBinaryDB", 1);
                    request.onupgradeneeded = (event) => {
                        const db = event.target.result;
                        db.createObjectStore("store");
                    };
                    request.onsuccess = (event) => {
                        const db = event.target.result;
                        const transaction = db.transaction(["store"], "readonly");
                        const objectStore = transaction.objectStore("store");
                        const getRequest = objectStore.get("sqlite_db");
                        getRequest.onsuccess = () => {
                            resolve(getRequest.result);
                        };
                        getRequest.onerror = () => {
                            resolve(null);
                        };
                    };
                    request.onerror = () => {
                        resolve(null);
                    };
                });
            },

            async saveDatabaseToIndexedDB() {
                if (!this.sqlDb) return;
                const binaryArray = this.sqlDb.export();
                return new Promise((resolve) => {
                    const request = window.indexedDB.open("ePlayStationBinaryDB", 1);
                    request.onsuccess = (event) => {
                        const db = event.target.result;
                        const transaction = db.transaction(["store"], "readwrite");
                        const objectStore = transaction.objectStore("store");
                        const putRequest = objectStore.put(binaryArray, "sqlite_db");
                        putRequest.onsuccess = () => {
                            resolve();
                        };
                        putRequest.onerror = () => {
                            console.error("Failed to save SQLite DB bytes to IndexedDB");
                            resolve();
                        };
                    };
                });
            },

            runQuery(sql, params = []) {
                if (!this.sqlDb) {
                    console.error("Database not initialized");
                    return;
                }
                this.sqlDb.run(sql, params);
                this.saveDatabaseToIndexedDB();
            },

            selectQuery(sql, params = []) {
                if (!this.sqlDb) return [];
                const stmt = this.sqlDb.prepare(sql);
                stmt.bind(params);
                const results = [];
                while (stmt.step()) {
                    results.push(stmt.getAsObject());
                }
                stmt.free();
                return results;
            },

            async runMigrations(schemas) {
                if (!this.sqlDb) return;
                console.log("Running local SQLite migrations...");
                for (const table in schemas) {
                    const ddl = schemas[table];
                    this.sqlDb.run(ddl);
                }
                // Create local sync_queue and local_metadata tables
                this.sqlDb.run("CREATE TABLE IF NOT EXISTS sync_queue (id INTEGER PRIMARY KEY AUTOINCREMENT, type TEXT, data TEXT, timestamp TEXT)");
                this.sqlDb.run("CREATE TABLE IF NOT EXISTS local_metadata (key TEXT PRIMARY KEY, value TEXT)");
                await this.saveDatabaseToIndexedDB();
                console.log("Migrations completed successfully.");
            },

            async importTableData(tableName, rows) {
                if (!rows || rows.length === 0) return;
                
                this.sqlDb.run(`DELETE FROM ${tableName}`);

                const tableInfo = this.selectQuery(`PRAGMA table_info(${tableName})`);
                const existingColumns = tableInfo.map(c => c.name);

                const firstRow = rows[0];
                const columns = Object.keys(firstRow).filter(col => existingColumns.includes(col));
                
                if (columns.length === 0) return;

                const columnsString = columns.join(', ');
                const placeholders = columns.map(() => '?').join(', ');
                const insertSql = `INSERT OR REPLACE INTO ${tableName} (${columnsString}) VALUES (${placeholders})`;

                const stmt = this.sqlDb.prepare(insertSql);
                for (const row of rows) {
                    const values = columns.map(col => {
                        const val = row[col];
                        if (typeof val === 'object' && val !== null) {
                            return JSON.stringify(val);
                        }
                        return val;
                    });
                    stmt.run(values);
                }
                stmt.free();
            },

            resolveRoute() {
                const path = window.location.pathname;
                if (path.includes('/device-control')) {
                    this.currentRoute = 'device-control';
                } else if (path.includes('/sales')) {
                    this.currentRoute = 'sales';
                } else if (path.includes('/waiting-lists')) {
                    this.currentRoute = 'waiting-lists';
                } else if (path.includes('/reservations')) {
                    this.currentRoute = 'reservations';
                } else if (path.includes('/play-sessions')) {
                    this.currentRoute = 'play-sessions';
                } else {
                    this.currentRoute = 'fallback';
                }
            },

            async fetchInitialCloudState() {
                this.isSyncingState = true;
                this.syncPercent = 5;
                this.syncStepName = 'الاتصال بالسيرفر وجلب البيانات...';
                try {
                    const response = await fetch('/tenant/offline/init');
                    const responseData = await response.json();

                    this.syncPercent = 25;
                    this.syncStepName = 'بدء معالجة جداول البيانات وتحديث الميجريشنز...';

                    if (responseData.schemas) {
                        await this.runMigrations(responseData.schemas);
                    }

                    this.syncPercent = 40;

                    if (responseData.data) {
                        const tables = Object.keys(responseData.data);
                        const totalTables = tables.length;
                        let processed = 0;
                        for (const tableName of tables) {
                            this.syncStepName = `جاري استيراد جدول: ${tableName}...`;
                            const rows = responseData.data[tableName];
                            await this.importTableData(tableName, rows);
                            processed++;
                            this.syncPercent = 40 + Math.floor((processed / totalTables) * 45); // scales from 40% to 85%
                        }
                    }

                    this.syncPercent = 90;
                    this.syncStepName = 'حفظ نسخة قاعدة البيانات محلياً...';

                    if (responseData.server_timestamp) {
                        this.runQuery("INSERT OR REPLACE INTO local_metadata (key, value) VALUES (?, ?)", ['last_sync_timestamp', responseData.server_timestamp]);
                    }
                    if (responseData.subscription_expiry) {
                        this.runQuery("INSERT OR REPLACE INTO local_metadata (key, value) VALUES (?, ?)", ['subscription_expiry', responseData.subscription_expiry]);
                    }

                    this.syncPercent = 95;
                    await this.saveDatabaseToIndexedDB();
                    await this.loadLocalData();

                    this.syncPercent = 100;
                    this.syncStepName = 'اكتملت التهيئة بنجاح!';
                    
                    // Show 100% success state for a moment
                    await new Promise(resolve => setTimeout(resolve, 800));
                } catch (e) {
                    console.error("Failed to fetch initial state, falling back to offline", e);
                    this.isOnline = false;
                    document.body.classList.add('is-offline');
                } finally {
                    this.isSyncingState = false;
                }
            },

            getPageTitle() {
                switch(this.currentRoute) {
                    case 'device-control': return 'التحكم في الأجهزة';
                    case 'sales': return 'المبيعات المباشرة';
                    case 'waiting-lists': return 'قائمة الانتظار';
                    case 'reservations': return 'الحجوزات اليومية';
                    case 'play-sessions': return 'جلسات اللعب الجارية';
                    default: return 'ePlayStation Cashier Desk';
                }
            },

            async loadLocalData() {
                if (!this.sqlDb) return;

                try {
                    this.devices = this.selectQuery("SELECT * FROM devices");
                } catch (e) { this.devices = []; }

                try {
                    this.catalogItems = this.selectQuery("SELECT * FROM catalog_items");
                } catch (e) { this.catalogItems = []; }

                try {
                    this.customers = this.selectQuery("SELECT * FROM customers");
                } catch (e) { this.customers = []; }

                try {
                    this.cashBoxes = this.selectQuery("SELECT * FROM cash_boxes");
                } catch (e) { this.cashBoxes = []; }

                try {
                    this.queueList = this.selectQuery("SELECT * FROM waiting_lists");
                } catch (e) { this.queueList = []; }

                try {
                    this.reservationList = this.selectQuery("SELECT * FROM reservations");
                } catch (e) { this.reservationList = []; }

                try {
                    this.activeSessionsList = this.selectQuery("SELECT * FROM play_sessions");
                } catch (e) { this.activeSessionsList = []; }

                try {
                    this.playSessionDetailsList = this.selectQuery("SELECT * FROM play_session_details");
                } catch (e) { this.playSessionDetailsList = []; }
                
                try {
                    const qRes = this.selectQuery("SELECT COUNT(*) as count FROM sync_queue");
                    this.queueCount = qRes[0] ? qRes[0].count : 0;
                } catch (e) { this.queueCount = 0; }

                if (this.cashBoxes.length > 0) {
                    this.posCashBoxId = this.cashBoxes[0].id;
                }

                try {
                    const lastSyncRes = this.selectQuery("SELECT value FROM local_metadata WHERE key = 'last_sync_timestamp'");
                    this.lastSyncTime = lastSyncRes[0] ? lastSyncRes[0].value : null;
                } catch (e) { this.lastSyncTime = null; }

                try {
                    const expiryRes = this.selectQuery("SELECT value FROM local_metadata WHERE key = 'subscription_expiry'");
                    this.subscriptionExpiry = expiryRes[0] ? expiryRes[0].value : null;
                } catch (e) { this.subscriptionExpiry = null; }

                this.checkSecurityLockout();
            },

            checkSecurityLockout() {
                if (!this.lastSyncTime) return;
                
                const lastSync = new Date(this.lastSyncTime);
                const now = new Date();
                const diffTime = Math.abs(now - lastSync);
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                
                if (diffDays > 7) {
                    this.isLocked = true;
                    this.lockReason = 'تم تجاوز الحد الأقصى للعمل أوفلاين (7 أيام). يرجى الاتصال بالإنترنت فوراً للتحقق من حالة الاشتراك ومزامنة البيانات.';
                }

                if (this.subscriptionExpiry) {
                    const expiry = new Date(this.subscriptionExpiry);
                    if (now > expiry) {
                        this.isLocked = true;
                        this.lockReason = 'لقد انتهت فترة الاشتراك الخاصة بك. يرجى الاتصال بالإنترنت للتجديد.';
                    }
                }
            },

            tickActiveDevices() {
                if (!this.sqlDb) return;
                const now = new Date();
                this.devices = this.selectQuery("SELECT * FROM devices").map(device => {
                    const devType = this.selectQuery("SELECT * FROM devices_types WHERE id = ?", [device.devices_type_id])[0];
                    device.type = devType || null;

                    const prices = this.selectQuery("SELECT * FROM play_prices WHERE device_id = ?", [device.id]);
                    device.play_prices = prices;

                    const session = this.activeSessionsList.find(s => s.device_id == device.id && s.status === 'active');
                    if (session) {
                        device.status = 'busy';
                        device.activeSession = session;
                        const details = this.playSessionDetailsList.filter(d => d.play_session_id == session.id);
                        const current = details[details.length - 1];
                        if (current && current.start_time) {
                            const start = new Date(current.start_time);
                            const diffSecs = Math.max(0, Math.floor((now - start) / 1000));
                            device.elapsedMinutes = diffSecs / 60;
                            const hourlyPrice = parseFloat(current.play_price || 0);
                            device.accumulatedCost = Math.round((device.elapsedMinutes * hourlyPrice) / 60);
                        }
                    } else {
                        device.status = 'available';
                        device.activeSession = null;
                        device.elapsedMinutes = 0;
                        device.accumulatedCost = 0;
                    }
                    return device;
                });
            },

            formatDuration(minutes) {
                if (!minutes) return "0 دقيقة";
                const hours = Math.floor(minutes / 60);
                const mins = Math.floor(minutes % 60);
                if (hours > 0) {
                    return `${hours} ساعة و ${mins} دقيقة`;
                }
                return `${mins} دقيقة`;
            },

            getDeviceName(id) {
                const d = this.devices.find(x => x.id == id);
                return d ? d.name : "جهاز PlayStation";
            },

            formatTime(timestamp) {
                if (!timestamp) return "";
                const d = new Date(timestamp);
                return d.toLocaleTimeString('ar-EG', { hour: '2-digit', minute: '2-digit' });
            },

            // DEVICE CONTROL ACTIONS
            async openStartModal(device) {
                this.targetDevice = device;
                this.playPrices = device.play_prices || [];
                this.startPlayPriceId = '';
                this.startType = 'open';
                this.startDuration = '';
                this.showStartModal = true;
            },

            async confirmStartSession() {
                const now = new Date();
                const sessionId = 'local_' + Date.now();
                const detailId = 'local_' + (Date.now() + 1);
                const selectedPrice = this.playPrices.find(x => x.id == this.startPlayPriceId);
                const hourlyPrice = selectedPrice ? selectedPrice.price : 0;

                this.runQuery(
                    "INSERT INTO play_sessions (id, device_id, status, customer_id, prepaid_amount, start_time, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)",
                    [sessionId, this.targetDevice.id, 'active', null, null, now.toISOString(), now.toISOString()]
                );

                this.runQuery(
                    "INSERT INTO play_session_details (id, play_session_id, play_price_id, play_price, start_time, created_at) VALUES (?, ?, ?, ?, ?, ?)",
                    [detailId, sessionId, this.startPlayPriceId, hourlyPrice, now.toISOString(), now.toISOString()]
                );

                const op = {
                    type: 'START_SESSION',
                    data: {
                        device_id: this.targetDevice.id,
                        play_price_id: this.startPlayPriceId,
                        duration_minutes: this.startType === 'limit' ? this.startDuration : null,
                        start_time: now.toISOString(),
                        local_session_id: sessionId,
                        local_detail_id: detailId
                    }
                };

                this.runQuery(
                    "UPDATE devices SET status = 'busy' WHERE id = ?",
                    [this.targetDevice.id]
                );

                await this.queueOperation(op);
                this.showStartModal = false;
                await this.loadLocalData();
            },

            async openCloseModal(device) {
                this.targetDevice = device;
                this.closePaidAmount = device.accumulatedCost || 0;
                this.showCloseModal = true;
            },

            async confirmCloseSession() {
                const now = new Date();
                const session = this.targetDevice.activeSession;
                
                const paid = parseFloat(this.closePaidAmount || 0);
                const total = parseFloat(this.targetDevice.accumulatedCost || 0);
                
                if (session) {
                    // Update Play Session locally
                    this.runQuery(
                        "UPDATE play_sessions SET status = 'completed', end_time = ?, total_amount = ? WHERE id = ?",
                        [now.toISOString(), total, session.id]
                    );

                    // Create local invoice
                    const localInvoiceId = 'local_' + Date.now();
                    this.runQuery(
                        "INSERT INTO invoices (id, invoice_no, play_session_id, device_id, total_amount, discount_amount, paid_amount, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                        [localInvoiceId, 'INV-OFFLINE-' + Date.now(), session.id, this.targetDevice.id, total, 0, paid, paid >= total ? 'paid' : 'partially_paid', now.toISOString()]
                    );

                    // Create local payment
                    if (paid > 0) {
                        const localPaymentId = 'local_' + (Date.now() + 1);
                        this.runQuery(
                            "INSERT INTO invoice_payments (id, invoice_id, amount, payment_method, cash_box_id, created_at) VALUES (?, ?, ?, ?, ?, ?)",
                            [localPaymentId, localInvoiceId, paid, 'cash', this.posCashBoxId || null, now.toISOString()]
                        );

                        // Create local ledger entry
                        const localLedgerId = 'local_' + (Date.now() + 2);
                        this.runQuery(
                            "INSERT INTO cash_box_ledger_entries (id, cash_box_id, type, amount, description, created_at) VALUES (?, ?, ?, ?, ?, ?)",
                            [localLedgerId, this.posCashBoxId || null, 'deposit', paid, 'تحصيل فاتورة جهاز: ' + this.targetDevice.name, now.toISOString()]
                        );

                        // Update Cash Box balance locally
                        if (this.posCashBoxId) {
                            this.runQuery(
                                "UPDATE cash_boxes SET current_balance = current_balance + ? WHERE id = ?",
                                [paid, this.posCashBoxId]
                            );
                        }
                    }
                }

                const op = {
                    type: 'CLOSE_SESSION',
                    data: {
                        device_id: this.targetDevice.id,
                        paid_amount: paid,
                        close_time: now.toISOString(),
                        cash_box_id: this.posCashBoxId || null
                    }
                };

                this.runQuery(
                    "UPDATE devices SET status = 'available' WHERE id = ?",
                    [this.targetDevice.id]
                );

                await this.queueOperation(op);
                this.showCloseModal = false;
                await this.loadLocalData();
            },

            // POS
            get filteredCatalog() {
                if (!this.searchProduct) return this.catalogItems;
                return this.catalogItems.filter(item => 
                    item.name.toLowerCase().includes(this.searchProduct.toLowerCase())
                );
            },

            addToCart(item) {
                const exist = this.cart.find(x => x.id === item.id);
                if (exist) {
                    exist.qty++;
                } else {
                    this.cart.push({
                        id: item.id,
                        name: item.name,
                        sale_price: parseFloat(item.sale_price),
                        qty: 1
                    });
                }
                this.calculateCart();
            },

            updateCartQty(id, diff) {
                const exist = this.cart.find(x => x.id === id);
                if (exist) {
                    exist.qty += diff;
                    if (exist.qty <= 0) {
                        this.cart = this.cart.filter(x => x.id !== id);
                    }
                }
                this.calculateCart();
            },

            calculateCart() {
                this.cartTotal = this.cart.reduce((sum, x) => sum + (x.sale_price * x.qty), 0);
            },

            async checkoutPOS() {
                const now = new Date();
                const itemsPayload = this.cart.map(x => ({
                    catalog_item_id: x.id,
                    quantity: x.qty,
                    unit_price: x.sale_price,
                    total_amount: x.sale_price * x.qty
                }));

                const total = this.cartTotal;
                const paid = this.cartTotal;

                const invoiceId = 'local_' + Date.now();
                const paymentId = 'local_' + (Date.now() + 1);
                const ledgerId = 'local_' + (Date.now() + 2);

                // Create local Invoice
                this.runQuery(
                    "INSERT INTO invoices (id, invoice_no, play_session_id, device_id, total_amount, discount_amount, paid_amount, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [invoiceId, 'INV-POS-OFFLINE-' + Date.now(), null, null, total, 0, paid, 'paid', now.toISOString()]
                );

                // Create local Payment
                this.runQuery(
                    "INSERT INTO invoice_payments (id, invoice_id, amount, payment_method, cash_box_id, created_at) VALUES (?, ?, ?, ?, ?, ?)",
                    [paymentId, invoiceId, paid, 'cash', this.posCashBoxId || null, now.toISOString()]
                );

                // Create local Ledger entry
                this.runQuery(
                    "INSERT INTO cash_box_ledger_entries (id, cash_box_id, type, amount, description, created_at) VALUES (?, ?, ?, ?, ?, ?)",
                    [ledgerId, this.posCashBoxId || null, 'deposit', paid, 'مبيعات مباشرة أوفلاين', now.toISOString()]
                );

                // Update Cash Box balance locally
                if (this.posCashBoxId) {
                    this.runQuery(
                        "UPDATE cash_boxes SET current_balance = current_balance + ? WHERE id = ?",
                        [paid, this.posCashBoxId]
                    );
                }

                const op = {
                    type: 'CREATE_DIRECT_SALE',
                    data: {
                        items: itemsPayload,
                        customer_id: this.posCustomerId || null,
                        cash_box_id: this.posCashBoxId || null,
                        paid_amount: paid,
                        local_invoice_id: invoiceId
                    }
                };

                await this.queueOperation(op);
                this.cart = [];
                this.cartTotal = 0;
                await this.loadLocalData();
            },

            // QUEUE
            async addToQueue() {
                if (!this.queueName) return;
                const localId = 'local_' + Date.now();
                const nowStr = new Date().toISOString();
                this.runQuery(
                    "INSERT INTO waiting_lists (id, customer_name, notes, time) VALUES (?, ?, ?, ?)",
                    [localId, this.queueName, this.queueNotes, nowStr]
                );
                const op = {
                    type: 'CREATE_QUEUE_ENTRY',
                    data: {
                        customer_name: this.queueName,
                        notes: this.queueNotes,
                        time: nowStr,
                        local_id: localId
                    }
                };
                await this.queueOperation(op);
                this.queueName = '';
                this.queueNotes = '';
                await this.loadLocalData();
            },

            async deleteFromQueue(id) {
                // Find item to get customer_name
                const items = this.selectQuery("SELECT customer_name FROM waiting_lists WHERE id = ?", [id]);
                const customerName = items[0] ? items[0].customer_name : '';
                this.runQuery("DELETE FROM waiting_lists WHERE id = ?", [id]);
                const op = {
                    type: 'DELETE_QUEUE_ENTRY',
                    data: {
                        id: id,
                        customer_name: customerName
                    }
                };
                await this.queueOperation(op);
                await this.loadLocalData();
            },

            // RESERVATIONS
            async addReservation() {
                if (!this.resName || !this.resTime) return;
                const localId = 'local_' + Date.now();
                const dateStr = new Date().toISOString().split('T')[0];
                this.runQuery(
                    "INSERT INTO reservations (id, customer_name, start_time, device_id, date) VALUES (?, ?, ?, ?, ?)",
                    [localId, this.resName, this.resTime, this.resDeviceId || null, dateStr]
                );
                const op = {
                    type: 'CREATE_RESERVATION',
                    data: {
                        customer_name: this.resName,
                        start_time: this.resTime,
                        device_id: this.resDeviceId || null,
                        date: dateStr,
                        local_id: localId
                    }
                };
                await this.queueOperation(op);
                this.resName = '';
                this.resTime = '';
                this.resDeviceId = '';
                await this.loadLocalData();
            },

            async deleteReservation(id) {
                // Find reservation to get customer_name
                const items = this.selectQuery("SELECT customer_name FROM reservations WHERE id = ?", [id]);
                const customerName = items[0] ? items[0].customer_name : '';
                this.runQuery("DELETE FROM reservations WHERE id = ?", [id]);
                const op = {
                    type: 'DELETE_RESERVATION',
                    data: {
                        id: id,
                        customer_name: customerName
                    }
                };
                await this.queueOperation(op);
                await this.loadLocalData();
            },

            // SYNC ENGINE
            async queueOperation(op) {
                const now = new Date();
                this.runQuery(
                    "INSERT INTO sync_queue (type, data, timestamp) VALUES (?, ?, ?)",
                    [op.type, JSON.stringify(op.data), now.toISOString()]
                );
                await this.loadLocalData();
            },

            async syncQueue() {
                if (this.syncing || !this.isOnline) return;
                this.syncing = true;
                try {
                    const queueItems = this.selectQuery("SELECT * FROM sync_queue ORDER BY id ASC");
                    if (queueItems.length === 0) {
                        this.syncing = false;
                        return;
                    }

                    const payload = queueItems.map(item => ({
                        id: item.id,
                        type: item.type,
                        data: JSON.parse(item.data)
                    }));

                    const response = await fetch('/tenant/offline/sync', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                        },
                        body: JSON.stringify({ queue: payload })
                    });

                    const resData = await response.json();
                    if (resData.results) {
                        for (const result of resData.results) {
                            if (result.status === 'success') {
                                this.runQuery("DELETE FROM sync_queue WHERE id = ?", [result.id]);
                            }
                        }
                    }
                    
                    await this.fetchInitialCloudState();
                } catch (e) {
                    console.error("Failed to sync queue, falling back to offline", e);
                    this.isOnline = false;
                    document.body.classList.add('is-offline');
                } finally {
                    this.syncing = false;
                    await this.loadLocalData();
                }
            }
        };
    }
</script>
