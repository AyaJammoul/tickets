<?php
require_once './comment.php';
require_once './Database.php';

//Here we have comment id so we call a function to get information of this id then edit it after that we call a function for updating in database

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $commentId = $_POST['comment_id'];
    $editedBody = $_POST['edit_body'];

    try {
        $comment = Comment::find($commentId); // Find the comment by ID
        $comment->body = $editedBody; // Update the body
        $comment->update(); // Call the update method

        echo json_encode(['status' => 200, 'msg' => 'Comment updated successfully.']);
    } catch (Exception $e) {
        echo json_encode(['status' => 500, 'msg' => 'Failed to update comment: ' . $e->getMessage()]);
    }
}
?>
