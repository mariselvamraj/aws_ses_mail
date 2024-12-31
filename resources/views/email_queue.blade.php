<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Queue Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">Email Queue Manager</h1>

        <!-- Email Form -->
        <form id="emailForm" action="{{ route('add-to-queue') }}" method="POST" class="mb-5">
            @csrf
            <div class="row">
                <div class="col-md-4">
                    <input type="email" name="email" class="form-control" placeholder=" To Email Address" required>
                </div>
                <div class="col-md-3">
                    <input type="text" name="subject" class="form-control" placeholder="Subject" required>
                </div>
                <div class="col-md-4">
                    <input type="text" name="body" class="form-control" placeholder="Body" required>
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-primary">Send Email</button>
                </div>
            </div>
        </form>

        <!-- Action Buttons -->
        <div class="mb-3">
            {{-- <button id="processQueueBtn" class="btn btn-success">Send Emails</button> --}}
            {{-- <form action="{{ route('send-message') }}" method="POST" style="display:inline-block;">
                @csrf
                <button type="submit" class="btn btn-success btn-sm">Send Emails</button>
            </form> --}}
        </div>


       

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
