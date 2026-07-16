<div class="card mb-3">
    <div class="card-header"><h3 class="card-title">Add Answer</h3></div>
    <div class="card-body">
        <form method="POST" action="{{ url('/api/v1/qa/questions/'.($question['id'] ?? '').'/answers') }}" enctype="multipart/form-data">
            <div class="form-group">
                <textarea name="body" class="form-control" rows="5" placeholder="Write a practical technical answer"></textarea>
            </div>
            <div class="form-group">
                <input name="attachment_media_ids[]" type="file" class="form-control" multiple>
            </div>
            <div class="custom-control custom-checkbox mb-3">
                <input type="checkbox" class="custom-control-input" id="answer_anonymous" name="is_anonymous" value="1">
                <label class="custom-control-label" for="answer_anonymous">Answer anonymously</label>
            </div>
            <button class="btn btn-primary" type="submit"><i class="bi bi-reply mr-1"></i> Submit Answer</button>
        </form>
    </div>
</div>