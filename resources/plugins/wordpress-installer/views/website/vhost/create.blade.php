@extends ('system.website.vhost.create')

@section ('custom_fields')
            <div class="row">
                <div class="large-12 column">
                    <label>
                        <input type="checkbox" name="installWordpress" value="true" />
                        Install Wordpress on this vHost
                    </label>
                </div>
            </div>
@endsection