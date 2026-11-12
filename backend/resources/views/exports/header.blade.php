<div class="divOne">
    <div class="divTwo">
        <p class="pOne">
            <strong>{{ $institutionName }}</strong>
        </p>
    </div>
    <div class="divThree">
        @if ($institutionLogo)
            <img class="imgOne"
                src="data:image/{{ mime_content_type(storage_path("app/$institutionLogo")) }};base64,{{ base64_encode(file_get_contents(storage_path("app/$institutionLogo"))) }}"
                alt="Logo">
        @endif
    </div>
</div>
<hr>
<br>
