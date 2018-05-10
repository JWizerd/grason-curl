<?php 

function custom_meta_box_markup()
{
    ?>
    <div>
            <label>Push Estate Sale to External Websites</label>
            <p style="color:red;">!IMPORTANT - <strong> Before you click the "Post" button, make sure that the content is absolutely ready to be published. Some external services do not have support for programmatically removing posts after they have been posted. In order to delete a post you will need to manually remove the post from [Facebook and LinkedIN business pages]</p>
            <button type="submit" name="post_to_external" class="button button-primary button-large">Post</button>
    </div>
    <?php 
}

function add_custom_meta_box()
{
    add_meta_box("demo-meta-box", "Post Estate Sale to External Websites", "custom_meta_box_markup", "estatesales", "side", "high", null);
}

// hide if post is not published

if (isset($_GET['post'])) {
    add_action("add_meta_boxes", "add_custom_meta_box");
}