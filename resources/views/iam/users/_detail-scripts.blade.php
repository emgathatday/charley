@push('scripts')
<script>
function setDetailTab(tab, panel){document.querySelectorAll('.dtab').forEach((el)=>el.classList.remove('active'));document.querySelectorAll('.tab-panel').forEach((el)=>el.classList.remove('active'));tab.classList.add('active');const target=document.getElementById('panel-'+panel);if(target)target.classList.add('active')}
function openDetailModal(id){const el=document.getElementById(id+'Modal');if(el)el.classList.add('show')}
function closeDetailModal(id){const el=document.getElementById(id+'Modal');if(el)el.classList.remove('show')}
function showDetailToast(message){const wrap=document.getElementById('detailToastContainer');if(!wrap)return;const toast=document.createElement('div');toast.className='toast';toast.textContent=message;wrap.appendChild(toast);setTimeout(()=>toast.remove(),2400)}
</script>
@endpush
