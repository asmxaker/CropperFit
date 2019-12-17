# CropperFit

### Create an image of the desired size with the original ratio and the size to fill the fields with the main color in the image 

Installation
<pre>
composer require asmx/cropper
</pre>
Usage
<pre>
use Asmx\Cropper\Cropper;

$cropper = new Cropper();

    $cropper->
    from('input_path_file')->
    to('output_path_file')->
    size([600,200])->
    fit();
</pre>

