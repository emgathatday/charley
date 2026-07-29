(function () {
  var CreateNewPartnerPage = {
    // Khoi tao trang create partner.
    init: function () {
      this.syncCheckedPlants();
    },

    // Preview logo sau khi admin chon file.
    previewLogo: function (event) {
      var file = event.target.files[0];
      if (!file) return;

      var preview = document.getElementById('logoPreview');
      var image = document.getElementById('logoImg');
      var name = document.getElementById('logoName');
      var reader = new FileReader();

      reader.onload = function (loadEvent) {
        if (image) image.src = loadEvent.target.result;
        if (name) name.textContent = file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';
        if (preview) preview.style.display = 'block';
      };

      reader.readAsDataURL(file);
    },

    // Xu ly nhap keyword tag bang Enter hoac dau phay.
    handleTagInput: function (event) {
      if (event.key === 'Enter' || event.key === ',') {
        event.preventDefault();
        this.addTagFromInput(event.target);
        return;
      }

      if (event.key === 'Backspace' && event.target.value === '') {
        this.removeLastTag();
      }
    },

    // Tao tag moi tu gia tri trong input.
    addTagFromInput: function (input) {
      var value = input.value.trim().replace(/,$/, '');

      if (!value) return;

      this.addTag(value);
      input.value = '';
    },

    // Chen tag moi vao truoc input.
    addTag: function (value) {
      var wrap = document.getElementById('tagWrap');
      var input = document.getElementById('tagInput');
      var tag = document.createElement('span');

      if (!wrap || !input) return;

      tag.className = 'tag';
      tag.innerHTML = value + '<button onclick="removeTag(this, event)">&times;</button>';
      wrap.insertBefore(tag, input);
    },

    // Xoa tag duoc bam nut remove.
    removeTag: function (button, event) {
      if (event) event.stopPropagation();
      if (button && button.parentElement) button.parentElement.remove();
    },

    // Xoa tag cuoi cung khi Backspace o input rong.
    removeLastTag: function () {
      var tags = document.querySelectorAll('#tagWrap .tag');

      if (tags.length) {
        tags[tags.length - 1].remove();
      }
    },
    syncCheckedPlants: function () {},
    togglePlant: function () {},

    // Hien toast rieng cua trang create partner.
    showToast: function (message, success) {
      var toast = document.getElementById('toast');
      var messageEl = document.getElementById('toastMsg');
      var icon = toast ? toast.querySelector('span:first-child') : null;
      var isSuccess = success !== false;

      if (!toast || !messageEl) {
        if (window.CharleyLayout) {
          window.CharleyLayout.showToast(message, isSuccess ? 'success' : 'error');
        }
        return;
      }

      messageEl.textContent = message;
      if (icon) icon.style.background = isSuccess ? '#10B981' : '#F43F5E';
      toast.style.transform = 'translateY(0)';
      toast.style.opacity = '1';

      window.setTimeout(function () {
        toast.style.transform = 'translateY(80px)';
        toast.style.opacity = '0';
      }, 3500);
    },

    // Tao partner demo, yeu cau phai chon tier truoc.
    createPartner: function () {
      var selectedTier = document.querySelector('input[name="partnerTier"]:checked');

      if (!selectedTier) {
        this.showToast('Please select a partner tier first.', false);
        return;
      }

      this.showToast('Partner account created successfully!', true);
    },

    // Luu ban nhap demo.
    saveDraft: function () {
      this.showToast('Draft saved.', true);
    }
  };

  window.CreateNewPartnerPage = CreateNewPartnerPage;

  window.previewLogo = function (event) { CreateNewPartnerPage.previewLogo(event); };
  window.handleTagInput = function (event) { CreateNewPartnerPage.handleTagInput(event); };
  window.removeTag = function (button, event) { CreateNewPartnerPage.removeTag(button, event); };
  window.togglePlant = function (checkbox) { CreateNewPartnerPage.togglePlant(checkbox); };
  window.createPartner = function () { CreateNewPartnerPage.createPartner(); };
  window.saveDraft = function () { CreateNewPartnerPage.saveDraft(); };

  document.addEventListener('DOMContentLoaded', function () {
    CreateNewPartnerPage.init();
  });
})();
