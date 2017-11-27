function cvs(canvasEl){
    this.canvas = canvasEl;
    this.ctx = this.canvas.getContext('2d');
    this.PI2 = Math.PI*2;

    this.drawRectangle = function (origin_x, origin_y, width, height)
    {
        //this.ctx.fillStyle = "rgba(0, 0, 0, 0)";
        this.ctx.beginPath();
        this.ctx.rect(origin_x, origin_y, width, height);
        this.ctx.closePath();
        this.ctx.fill();
    }

    this.drawCircle = function (x,y,radius,r,g,b,alpha){
        //this.ctx.fillStyle = "rgba("+r+", "+g+", "+g+", "+a+")";
        this.ctx.beginPath();
        this.ctx.arc(x, y, radius, 0, this.PI2, true);
        this.ctx.closePath();
        this.ctx.fill();
    }

    // Clear a canvas with a specified color
    this.clear2d = function ()
    {
        this.drawRectangle(0, 0, this.canvas.width, this.canvas.height);
    }
    this.getMousePosition = function(e){
        var pos = new Array();
        pos.x = e.pageX - this.canvas.offsetLeft;
        pos.y = e.pageY - this.canvas.offsetTop;
        return pos;
    }
}
//fps stuff
var fps = 0;
function checkFps() {
    //use this function with
    //setInterval(checkFps, 1000);
    //fps++;
   $("#fps").html(fps);
   fps = 0;
}

