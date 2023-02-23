<!-- 配置文件 -->
<script type="text/javascript" src="{{ asset('vendor/ueditor-plus/ueditor.config.js') }}"></script>
<!-- 编辑器源码文件 -->
<script type="text/javascript" src="{{ asset('vendor/ueditor-plus/ueditor.all.js') }}"></script>
<script>
    window.UEDITOR_CONFIG.serverUrl = '{{ config('ueditor-plus.server_url') }}'
	// 多用户接口
	{{--window.UEDITOR_CONFIG.serverUrl = '{{ config('ueditor-plus.server_url') }}?user={{Auth::id()}}'--}}
</script>