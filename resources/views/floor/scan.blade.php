<x-floor-layout title="Phone scan">
    <p class="text-secondary small mb-3">
        Trial for one sewing line. Scan the bundle ticket with the phone camera, or type the production / work order number.
        Each scan logs pcs on the line and adds them to stitching qty.
    </p>

    <form action="{{ route('floor.scan.store') }}" method="POST" id="floor-scan-form" class="mb-4">
        @csrf
        <div class="mb-3">
            <label class="form-label" for="production_line_id">Line</label>
            <select name="production_line_id" id="production_line_id" class="form-select" required>
                @foreach($lines as $line)
                    <option value="{{ $line->id }}" @selected((int) old('production_line_id', $selectedLineId) === (int) $line->id)>
                        {{ $line->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label" for="code">Barcode / order no.</label>
            <input type="text" name="code" id="code" class="form-control"
                   value="{{ old('code') }}" required autofocus autocomplete="off"
                   inputmode="text" placeholder="Scan or type" enterkeyhint="go">
        </div>
        <div class="mb-3">
            <label class="form-label" for="pcs">Pcs this scan</label>
            <input type="number" name="pcs" id="pcs" class="form-control" min="1" max="5000"
                   value="{{ old('pcs', 1) }}" required>
        </div>
        <button type="submit" class="btn btn-success w-100 btn-scan mb-2">
            <i class="bi bi-upc-scan me-1"></i> Log pcs
        </button>
        <button type="button" class="btn btn-outline-light w-100 btn-scan" id="floor-camera-btn" hidden>
            <i class="bi bi-camera me-1"></i> Use camera
        </button>
    </form>

    <video id="floor-camera" playsinline hidden></video>

    @if($recent->isNotEmpty())
        <h2 class="h6 text-secondary mt-4">Your last scans</h2>
        <ul class="list-unstyled small mb-0">
            @foreach($recent as $row)
                <li class="border-bottom border-secondary py-2 d-flex justify-content-between">
                    <span>{{ $row->productionOrder?->order_number }} · {{ $row->line?->name }}</span>
                    <span>{{ $row->pcs }} pcs</span>
                </li>
            @endforeach
        </ul>
    @endif

    @push('scripts')
    @endpush
    <script>
        (function () {
            var btn = document.getElementById('floor-camera-btn');
            var video = document.getElementById('floor-camera');
            var input = document.getElementById('code');
            var form = document.getElementById('floor-scan-form');
            if (!btn || !('BarcodeDetector' in window) || !navigator.mediaDevices) {
                return;
            }
            btn.hidden = false;
            var running = false;
            btn.addEventListener('click', async function () {
                if (running) return;
                running = true;
                btn.hidden = true;
                video.hidden = false;
                try {
                    var stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
                    video.srcObject = stream;
                    await video.play();
                    var detector = new BarcodeDetector({ formats: ['code_128', 'code_39', 'qr_code', 'ean_13'] });
                    var tick = async function () {
                        if (!running) return;
                        try {
                            var codes = await detector.detect(video);
                            if (codes.length) {
                                input.value = codes[0].rawValue;
                                stream.getTracks().forEach(function (t) { t.stop(); });
                                video.hidden = true;
                                form.submit();
                                return;
                            }
                        } catch (e) {}
                        requestAnimationFrame(tick);
                    };
                    requestAnimationFrame(tick);
                } catch (e) {
                    running = false;
                    btn.hidden = false;
                    video.hidden = true;
                    alert('Camera was blocked. Type the code instead.');
                }
            });
        })();
    </script>
</x-floor-layout>
