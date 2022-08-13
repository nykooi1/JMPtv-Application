<?php


// Only lowercase alpha-numeric, dashes, and underscores
function vLabel($S) { return preg_replace("/[^a-z0-9-_]/", "", strtolower(substr(trim($S), 0, 144))); }

$vShow = "vikings";
	
function getImagesJSON($vShow){
	return file_get_contents("/var/www/html/accounts/" . $vShow . "/json/slides.json");	
}
?>
<html>
<head>
<title>JMPtv</title>
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" integrity="sha384-JcKb8q3iqJ61gNV9KGb8thSsNjpSL0n8PARn9HuZOnIxN0hoP+VmmDGMN5t9UJ0Z" crossorigin="anonymous">
<script src="https://code.jquery.com/jquery-3.5.1.min.js" integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js" integrity="sha384-B4gt1jrGC7Jh4AgTPSdUtOBvfO8shuf57BaghqFfPlYxofvL8/KUEfYiJOMMV+rV" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/socket.io/2.3.0/socket.io.js"></script>
<style>

#vVideo{
	z-index:-2;
	position:absolute;
	width:100%;
	height:100%;
}

.vBase {
	z-index: -1;
	position:absolute;
	height:100%;	
	width:100%;
	margin:0;
	padding:0;
	background-position: center center;
	background-repeat: no-repeat;
	background-size: cover;
	background-color:black;
	opacity:0;
}
.vCover {
	opacity:1;
	height:100%;	
	width:100%;
	margin:0;
	padding:0;
	background-position: center center;
	background-repeat: no-repeat;
	background-size: cover;
	background-color:black;
	opacity:0;
}
.vClickScreen {
	position: absolute;
	top: 0;
	left:0;
	opacity: 0;
	height:100%;	
	width:100%;
	margin:0;
	padding:0;
}
.btn{
	border-radius:0;
	width:150px;
}
.insertBtn, .startBtn{
	background-color:#000099;
	color:#ffffff;
}
.startBtn{
	position: fixed;
	top: 50%;
	left: 50%;
	transform: translate(-50%, -50%);
}
.deleteBtn{
	background-color:#990000;
	color:#ffffff;
}
.modal-backdrop{
    opacity:0.9 !important;
}
.vUpload-wrapper {
    position: relative;
    overflow: hidden;
    display: inline-block;
}

.vUpload-wrapper input[type=file] {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 50px;
    opacity: 0;
}

input[type="file"] {
    display: none;
}

#vNoImages{
	top: 0;
	position: absolute;
	width: 100%;
	height: 100%;
	background-color: black;
	display:none;
}

</style>
</head>
<body style="background-color:black;">
<video id="vVideo" autoplay loop muted><source src="https://jmptv.com/accounts/vikings/fullvid.mp4" type="video/mp4"></source></video>
<div class="vBase"></div>
<div class="vCover"></div>
<div id="vClickScreen" class="vClickScreen" onclick="vEdit()"></div>
<!-- Modal -->
<div class="modal fade" id="modal" tabindex="-1" role="dialog" aria-labelledby="modalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
      	<center style="width:100%;">
        	<h5 class="modal-title" id="modalLabel" style="margin-left:48px;">Edit Slides</h5>
        </center>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
      	<center>
      		<!-- Insert Slide -->
      		<form id="vForm" method="post" enctype="multipart/form-data">
		      <div class="form-group">
		      	<label for="vFile" class="btn insertBtn">
    				<i class="fa fa-cloud-upload"></i> Insert Slide
				</label>
				<input id="vSlide" name="vSlide"  style="display:none" value="">
				<input id="vAccount" name="vAccount" style="display:none;" value="<?php echo $vShow; ?>">
		        <input id="vFile" name="vFile" type="file" class="form-control-file"  accept="image/jpeg, image/png">
		      </div>
    		</form>
      		<!-- Delete Slide  -->
      		<button type="button" class="btn deleteBtn" data-dismiss="modal" onclick="vDeleteSlide()">Delete Slide</button>
      	</center>
      </div>
    </div>
  </div>
</div>
<script>

var vShow = "/<?php echo $vShow; ?>/media/";

var vAccount = "<?php echo $vShow; ?>";

//DEBUG
console.log(vAccount);

var vSlides = '<?php echo preg_replace("/[\n\r]/", "", getImagesJSON($vShow)); ?>';

var vTrans = 1000; // 1 second transition
var vWait = 6000; // 6 seconds per slide
var vSlide = -1;
var vTimerHandle;
var ttl = 480000;

if(vSlides == ""){
	vSlides = Array();
} else {
	vSlides = JSON.parse(vSlides);
}

//CONNECTION TO PORT
var socket = io.connect("https://jmptv.com:4000");

//refresh this page when the server commands it
socket.on("refresh", function(){
	window.location.reload();
});


//hide the image slider, start the video from beginnign
function vStartVideo(){
	//hide the slider
	//add transition
        $(".vCover").css("transition", "1s ease");
        $(".vBase").css("transition", "1s ease");
        //fade in
        $(".vCover").css("opacity","0");
        $(".vBase").css("opacity","0");
        //remove transition
        setTimeout(function(){
        	$(".vCover").css("transition", "none");
        	$(".vBase").css("transition", "none");
        },1000);
	$(".deleteBtn").css("visibility", "hidden");
	//start the video from the beginning;
	//document.getElementById("vVideo").src = "";
	//document.getElementById("vVideo").src = "https://jmptv.com/accounts/classrockcoffeelv/classrock.mp4";
	//document.getElementById("vVideo").play();
	var video = document.getElementById("vVideo");
	video.pause();
	video.currentTime = 0;
	video.load();
}

/************ FORM HANDLING **************/

//Attach a submit handler to the image submit form
$("form#vForm").submit(function(e) {
	e.preventDefault();    
	var formData = new FormData(this);
	$.ajax({
		url: "https://jmptv.com/image.php",
		type: 'POST',
		data: formData,
		//responds with the filename after it has been created
		success: function (data) {
			document.getElementById("vFile").value = "";
			//tell the front end to update the slides
			var insertInfo = Array();
			//send account
			insertInfo.push("<?php echo $vShow; ?>");
			//send slide key
			insertInfo.push(vSlide);
			//send file name
			insertInfo.push(data);
			socket.emit("insertedImage", insertInfo);
			setTimeout(function(){
				socket.emit("editingFileStatus", [vAccount, "false"]);
			},2000);
		},
		cache: false,
		contentType: false,
		processData: false
	});
});

//when user click delete slide
function vDeleteSlide(){
	var deleteInfo = Array();
	deleteInfo.push("<?php echo $vShow; ?>");
	deleteInfo.push(vSlide);
	socket.emit("deleteImage", deleteInfo);
	socket.emit("editingFileStatus", [vAccount, "false"]);
}


//UPDATED
//always listening, whenever it hears "updateSlides", update the slides
socket.on("updateSlides", function(data){	
	//check if the changes were made for THIS account
	if(data["vAccount"] == vAccount){
		vSlides = JSON.parse(data["vImages"]);
		if(vSlides.length == 0){
			$(".deleteBtn").css("display", "none");
		}else{
			$(".deleteBtn").css("display", "block");
		}
		vTimerHandle = setTimeout(function(){vLooper();}, 0);
		//wait for the next slide to load in (animate) before allowing edting again
		setTimeout(function(){
			$('#vClickScreen').attr('onclick', 'vEdit()');
		}, 2000);
	}
});

//whenever a slide is deleted, subtract one from the index
socket.on("deletedSlide", function(data){
	if(data == vAccount){
		vSlide--;
	}
});

socket.on("editing", function(data){
	if(data == vAccount){
		clearTimeout(vTimerHandle);
		$('#vClickScreen').attr('onclick', '');
	}	
});

socket.on("enableEditing", function(data){
	console.log("enable editing for:" + data);
	if(data == vAccount){
		$('#vClickScreen').attr('onclick', 'vEdit()');
	}	
});

socket.on("startTimeout", function(data){
	if(data == vAccount){
		vTimerHandle = setTimeout(function(){vLooper();}, 0);
	}	
});

//On submit of the file, trigger the submit event on the form
//Also, when they select a file, hide the modal, it will be DISABLED until it finishes uploading (on updated slides socket event)
$("#vFile").change(function(){
	$('#modal').modal('hide');
	$("#vForm").submit();
});


/************** Slide Logic ***************/

//EXPLANATION: of vSlide & vNextSlide: we have a one second buffer to load in the image
//this means that we have to wait one second to actually modify the vSlide being sent to the socket (VISUALLY CORRECT)
//in order to get around this we use a temporary vSlideTemp variable to load the new / next slide image into the body (+1), then after one second
//the image will actually fade out which is when we actually modify the vSlide value

//Slide Logic:
//change body to new image while invisible
//wait one second to allow body to load, THEN fade out cover
//update cover to new image, show cover
function vLooper(){
	console.log("vLooper");
	if(vSlides.length > 0){
		$(".deleteBtn").css("visibility", "visible");
	}
	//move on to next slide
	//note: the reason we have this is to delay updating the actual vSlide until the image changes
	//logically it is not necessary, but visually the slide has a one second buffer
	var vNextSlide = vSlide + 1;
	//if we just finished the last slide, go back to the beginning
	if(vNextSlide >= vSlides.length){
		vStartVideo();
		clearTimeout(vTimerHandle);
		$(".vBase").css("background-image", "url('" + vSlides[0] + "')");
		$(".vCover").css("background-image", "url('" + vSlides[0] + "')");
		vSlide = -1;
		vTimerHandle = setTimeout(function(){
			//add transition
                	$(".vCover").css("transition", "1s ease");
                	$(".vBase").css("transition", "1s ease");
                	//fade in
                	$(".vCover").css("opacity","1");
                	$(".vBase").css("opacity","1");
               		//remove transition
                	setTimeout(function(){
                	        $(".vCover").css("transition", "none");
                	        $(".vBase").css("transition", "none");
                	},1000);
			vLooper();
		}, 121000);
		return;
	}
	//if there are no images
	if(vSlides.length == 0){
		vSlide = 0;
		$(".vBase").css("background-image", "url('')");
		$(".vCover").css("background-image", "url('')");	
		clearTimeout(vTimerHandle);
        	vTimerHandle = setTimeout(function(){
                	vLooper();
       	 	}, vWait);
	}
	//base case
	//if there is only one image, display the one image
	else if(vSlides.length == 1){
		console.log("one slide");
		vSlide = 0;
		$(".vCover").css("opacity","1");
                $(".vBase").css("opacity","1");
		$(".vBase").css("background-image", "url('" + vSlides[0] + "')");
		$(".vCover").css("background-image", "url('" + vSlides[0] + "')");	
		clearTimeout(vTimerHandle);
        	vTimerHandle = setTimeout(function(){
                	vLooper();
        	}, vWait);
	}
	//if there is more than one image, loop through them
	else if(vSlides.length > 1){
		$(".vCover").css("opacity","1");
                $(".vBase").css("opacity","1");
		//change body to new image while invisible
		$(".vBase").css("background-image", "url('" + vSlides[vNextSlide] + "')");
		//fade out cover
		setTimeout(function(){
			vSlide++;
			if(vSlide >= vSlides.length){
				vSlide = 0;
			}
			$(".vCover").fadeOut(1000);
		}, 1000);
		//update cover + show cover
		//note: here we are waiting for the cover to completely fade out...
		//the animation takes 1 second, but we wait one more to avoid glitchiness
		setTimeout(function(){
			$(".vCover").css("background-image", "url('" + vSlides[vSlide] + "')");	
	    	$(".vCover").css("display", "block"); 
	    	$(".vCover").css("opacity", "1");  	
		}, 2500);
		clearTimeout(vTimerHandle);
        	vTimerHandle = setTimeout(function(){
                	vLooper();
        	}, vWait);
	}		
}


//triggered onclick of screen
//opens modal, emits the editing event which clears the timeout (stops slides) for ALL USERS, and disables the modal
function vEdit() {
	$('#modal').modal('show');
	socket.emit("editing", vAccount);
	socket.emit("editingFileStatus", [vAccount, "true"]);
}

//on click of the modal background OR the modal exit button, put the onclick event back
//this is because if they closed the modal manually without inserting or deleting there is no need to disable the modal
//only disable modal while waiting for delete or insert to FINISH
//- for background modal click
$('.modal').on('click', function(e) {
	if (e.target !== this)
		return;
	//$('#vClickScreen').attr('onclick', 'vEdit()');
	//allow editing again
	socket.emit("enableEditing", vAccount);
	socket.emit("editingFileStatus", [vAccount, "false"]);
	//continue the slides for everyone
	socket.emit("startTimeout", vAccount);	
});
//-for exit button modal click
$(".close").on('click', function(){
	//$('#vClickScreen').attr('onclick', 'vEdit()');
	//allow editing again
	socket.emit("enableEditing", vAccount);
	socket.emit("editingFileStatus", [vAccount, "false"]);
	//continue the slides for everyone
	socket.emit("startTimeout", vAccount);	
});

//if there are slides, initialize the body to show the first one
if(vSlides.length > 0){
	$(".vBase").css("background-image", "url('" + vSlides[0] + "')");
	$(".vCover").css("background-image", "url('" + vSlides[0] + "')");
	$("#vSlide:hidden").val(0);
}

//onclick of delete, hide the modal
$(".deleteBtn").on("click", function(){
	$('#modal').modal('hide');
});

$('#modal').on('hidden.bs.modal', function () {
	socket.emit("editingFileStatus", [vAccount, "false"]);
})

socket.emit("account", vAccount);

function uuidv4() {
  return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
    var r = Math.random() * 16 | 0, v = c == 'x' ? r : (r & 0x3 | 0x8);
    return v.toString(16);
  });
}

//on initial page, if someone is editing, disable editing
//when they are done it will allow editign again through sockets
$.get("accounts/" + vAccount + "/editing.json?" + uuidv4(), function(data) {
	var numAccountsWithModalOpen = Object.keys(data).length;
	if(numAccountsWithModalOpen == 1){
		$('#vClickScreen').attr('onclick', '');
	}
});

//initial looper call to start the slides
//the image slider starts off as hidden
$(".deleteBtn").css("visibility", "hidden");
if(vSlides.length > 0){
	setTimeout(function(){
		//add transition
		$(".vCover").css("transition", "1s ease");
		$(".vBase").css("transition", "1s ease");
		//fade in
		$(".vCover").css("opacity","1");
		$(".vBase").css("opacity","1");
		//remove transition
		setTimeout(function(){
			$(".vCover").css("transition", "none");
                	$(".vBase").css("transition", "none");
		},1000);
		$(".deleteBtn").css("visibility", "visible");
		vLooper();
	}, 121000);
}else{
	vTimerHandle = setTimeout(function(){vLooper()}, 121000);
}

//need simple socket to refresh


document.getElementById("vVideo").crossOrigin = "anonymous";

</script>
</body>
</html>
