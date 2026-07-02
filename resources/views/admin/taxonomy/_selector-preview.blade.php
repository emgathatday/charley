<div class="card card-outline card-secondary">
    <div class="card-header">
        <h3 class="card-title mb-0">Reusable Selector Preview</h3>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <x-taxonomy.selector
                    :tags="$selectorTags"
                    :selected="$selectedTagIds ?? []"
                    name="preview_tag_ids"
                    label="Tag Selector"
                    help="Reusable multi-select component for tagged content forms."
                />
            </div>
            <div class="col-md-6">
                <label class="form-label">Tag Chips</label>
                <x-taxonomy.chips :tags="$selectorTags->take(6)" />
            </div>
        </div>
    </div>
</div>
