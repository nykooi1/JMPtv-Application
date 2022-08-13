var fs = require( 'fs' );
var express = require("express");
var socket = require("socket.io");

//APP
var app = express();
var https = require("https");
var server = https.createServer( {
        "key" : fs.readFileSync("/etc/apache2/ssl/media9.key"),
        "cert" : fs.readFileSync("/etc/apache2/ssl/media9-chained.crt")
        }
        , app);

server.listen(4000, function(){
        console.log("listening to requests on prt 4000");
});

app.use(express.static("index.php"));

//Socket Setup
var io = socket(server);

var socketAccounts = {};

io.on("connection", function(socket){
	
	//a users is currently editing
    socket.on("refresh", function(){
    	io.sockets.emit("refresh");
    });
	
	socket.on("account", function(data){
		
		console.log("\n-----------------------\n");
		
		socketAccounts[socket.id] = data;
		console.log("All Accounts:");
		console.log(socketAccounts);
		console.log("account name:" + socketAccounts[socket.id]);
	});
	
	//when the socket disconnects, remove the user from the object IF IT EXISTS
	socket.on('disconnect',function(){
		
		console.log("\n-----------------------\n");
		
		var accountName = socketAccounts[socket.id];
		
		console.log("delete account name:" + socketAccounts[socket.id]);
		
		fs = require('fs');
	 	fs.readFile('html/accounts/' + accountName + '/editing.json', 'utf8', function (err,fileData) {
			if (err) {
				return console.log(err);
			}
			
			//decode the JSON into a javascript object 
			var socketEditingStatus = JSON.parse(fileData);
			
			//check if the modal is open for this key (aka is it in the array)
			//if it is, delete it
			//else do nothing
			if(socketEditingStatus[socket.id] == 1){
				//delete the socket object if it exists, else do nothing
				delete socketEditingStatus[socket.id];
				
				console.log("All Accounts:");
				console.log(socketAccounts);
				console.log("account name:" + accountName);
				
				//convert back to JSON
				var socketEditingStatusJSON = JSON.stringify(socketEditingStatus);
				
				//write the updated socket status JSON object back thefile
				fs.writeFile('html/accounts/' + accountName + '/editing.json', socketEditingStatusJSON, (err) => { 
					if (err){
				    	return console.log(err); 
					}
					io.sockets.emit("enableEditing", accountName);
				});
			}
			
			//delete the socket id from the array
			delete socketAccounts[socket.id];
	 	});
	});
	
	console.log("made socket connection", socket.id);
	
	//delete image	
	socket.on("deleteImage", function(data){	
		var vAccount = data[0];
		var vSlide = data[1];
		//read the file 
		fs = require('fs');
		fs.readFile('html/accounts/' + vAccount + '/json/slides.json', 'utf8', function (err,fileData) {
			if (err) {
				return console.log(err);
			}
			//get the original list of images
			var originalImagesArray = JSON.parse(fileData);
			//get the filename of the one we want to delete
			var fileToDelete = "html/" + originalImagesArray[vSlide];
			//delete the specified image
			originalImagesArray.splice(vSlide, 1);
			var updatedImagesArray = JSON.stringify(originalImagesArray);
			//now write the updated array to the file
			fs.writeFile('html/accounts/' + vAccount + '/json/slides.json', updatedImagesArray, (err) => { 
				if (err){
			    	return console.log(err); 
				}
				//subtract one from vSlide
				io.sockets.emit("deletedSlide", vAccount);
				
				//account name, images array
				var updatedAccountImagesArray = {};
				updatedAccountImagesArray["vAccount"] = vAccount;
				updatedAccountImagesArray["vImages"] = updatedImagesArray;

				//tell the front end to refresh the slides
				io.sockets.emit("updateSlides", updatedAccountImagesArray);
			}); 
			//delete the image file
			fs.unlink(fileToDelete, (err) => {
				if (err) throw err;
				console.log('successfully deleted ' + fileToDelete);
			});
		});
    });
    
    //a users is currently editing
    socket.on("editing", function(data){
    	io.sockets.emit("editing", data);	
    });
    
    socket.on("editingFileStatus", function(data){
		
		console.log("\n-----------------------\n");
		
		console.log("editing json file (adding or removing)");
		
	 	//read the editing json file
	 	fs = require('fs');
	 	fs.readFile('html/accounts/' + data[0] + '/editing.json', 'utf8', function (err,fileData) {
			if (err) {
				return console.log(err);
			}
			//decode the JSON into a javascript object 
			var socketEditingStatus = JSON.parse(fileData);
			
			//if they are no longer editing, remove it from the array
			if(data[1] == "false"){
				delete socketEditingStatus[socket.id];
			}
			//if they are editing, add it into the array
			else {
				socketEditingStatus[socket.id] = 1;
			}
			
			//convert back to JSON
			var socketEditingStatusJSON = JSON.stringify(socketEditingStatus);
			//write the updated socket status JSON object back thefile
			fs.writeFile('html/accounts/' + data[0] + '/editing.json', socketEditingStatusJSON, (err) => { 
				if (err){
			    	return console.log(err); 
				}
			});
	 	});
	 });
    
    //re enable editing for all users
    socket.on("enableEditing", function(data){
    	io.sockets.emit("enableEditing", data);	
	console.log(data);
    });
    
    //continue sliding
    socket.on("startTimeout", function(data){
    	io.sockets.emit("startTimeout", data);	
    });
    
    //inserted image	
	socket.on("insertedImage", function(data){	
		
		var vAccount = data[0];
		var vSlide = data[1];
		var vFilename = data[2];
		
		fs = require('fs');
		
		//access the file
		fs.access('html/accounts/' + vAccount + '/json/slides.json', fs.F_OK, (err) => {
			//if file does not exist
			if (err) {
				
				var imageArray = Array();
				imageArray.push(vFilename);
				var imageArrayJSON = JSON.stringify(imageArray);
				
				//make the file
		    	fs.writeFile('html/accounts/' + vAccount + '/json/slides.json', imageArrayJSON, function (err) {
					if (err) throw err;
					console.log('Saved!');

					//account name, images array
                                	var updatedAccountImagesArray = {};
                                	updatedAccountImagesArray["vAccount"] = vAccount;
                                	updatedAccountImagesArray["vImages"] = imageArrayJSON;

					io.sockets.emit("updateSlides", updatedAccountImagesArray);
				});
			}
			//if file already exists
			else {
				fs.readFile('html/accounts/' + vAccount + '/json/slides.json', 'utf8', function (err,fileData) {
					
					if (err) {
						return console.log(err);
					}
					//get the original list of images
					var originalImagesArray = JSON.parse(fileData);
					
					console.log("originalImages: " + originalImagesArray);
					
					//insert the specified image filename
					originalImagesArray.splice(vSlide + 1, 0, vFilename);
					
					console.log(vSlide);
					
					var updatedImagesArray = JSON.stringify(originalImagesArray);
					
					console.log("updatedImagesArray: " + updatedImagesArray);
					
					//now write the updated array to the file
					fs.writeFile('html/accounts/' + vAccount + '/json/slides.json', updatedImagesArray, (err) => { 
						if (err){
					    	return console.log(err); 
						}
						
						//account name, images array
                                        	var updatedAccountImagesArray = {};
                                        	updatedAccountImagesArray["vAccount"] = vAccount;
                                        	updatedAccountImagesArray["vImages"] = updatedImagesArray;

						//tell the front end to refresh the slides
						io.sockets.emit("updateSlides", updatedAccountImagesArray);
					}); 
					
				});
			}
		})
    });
});


