<div class="agnstk-hello-service">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="bi bi-emoji-smile"></i>
                AGNSTK Hello Service
            </h3>
        </div>
        <div class="card-body">
            <div class="alert alert-success">
                <h4>{{ $message }}</h4>
                <p class="mb-2">
                    <strong>Platform:</strong> {{ $platform }}<br>
                    <strong>Generated:</strong> {{ $timestamp }}
                </p>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <h5>Available as:</h5>
                    <ul class="list-unstyled">
                        <li><i class="bi bi-layout-text-window"></i> Block (in page builders)</li>
                        <li><i class="bi bi-code-square"></i> Shortcode: <code>[hello]</code></li>
                        <li><i class="bi bi-file-earmark"></i> Page: <a href="{{ base_url('hello') }}">/hello</a></li>
                        <li><i class="bi bi-list"></i> Menu item</li>
                        <li><i class="bi bi-cloud"></i> API endpoint</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h5>Deployment targets:</h5>
                    <ul class="list-unstyled">
                        <li><i class="bi bi-globe"></i> Web application</li>
                        <li><i class="bi bi-display"></i> Desktop app</li>
                        <li><i class="bi bi-phone"></i> Mobile app (PWA/Native)</li>
                        <li><i class="bi bi-terminal"></i> Command line</li>
                        <li><i class="bi bi-wordpress"></i> CMS plugins</li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="card-footer text-muted">
            <small>
                This is a single service that works across all AGNSTK deployment targets.
                <br>
                Edit <code>src/Services/HelloService.php</code> to customize.
            </small>
        </div>
    </div>
</div>
