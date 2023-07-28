(function ($){
    window.getCursorDistance = function(point1, point2) {
        let x = point1.x - point2.x;
        let y = point1.y - point2.y;
        return Math.sqrt(x * x + y * y);
    }

    function getOffset(element){
        return {
            left: element.offset().left + parseInt(element.css('border-left-width')) -  document.documentElement.scrollLeft,
            top: element.offset().top + parseInt(element.css('border-top-width')) - document.documentElement.scrollTop
        }
    }

    $.zoomable = function(el, options){
        let base = this;
        base.$el = $(el);
        base.el = el;

        base.$el.data('zoomable', base);

        base.zoomIn = function(){
            base._zoom({
                type: 'zoomin'
            })
        };
        base.zoomOut = function(){
            base._zoom({
                type: 'zoomout'
            })
        };

        base.init = function (){
            base.options = $.extend({}, $.zoomable.defaultOptions, options);
            base._zooming = false;
            base._zoom_object = null;
            base._zoom_touches = null;

            base.$el
                .on('touchstart', function(e){
                    e.preventDefault();
                    let fingers = e.originalEvent.touches.length;
                    if(fingers === 2){
                        base._zoomStart(e);
                    }
                })
                .on('wheel', function(e){
                    e.stopPropagation();
                    base._zoom(e);
                });
            $('body')
                .on('touchend', function(e){
                    e.preventDefault();
                    let event = e.originalEvent;
                    if((event.changedTouches.length === 1 && event.touches.length === 1) && base._zooming)
                        base._zoomEnd(e);
                })
                .on('touchmove', function (e){
                    let fingers = e.originalEvent.touches.length;
                    if(fingers === 2)
                        base._zoom(e);
                });

            if(base.options.onZoom !== undefined)
                base.onZoom = base.options.onZoom;

            base.$el.toggleClass('zoomable', true);
        }

        base._zoomStart = function(e){
            e.preventDefault();
            if($(e.target).closest('.zoomable').length){
                if(e.type === 'touchstart'){
                    if(e.touches.length === 0) return;
                    base._zoom_object = $(e.target).closest('.zoomable')[0];
                    base._zooming = true;
                    e = e.originalEvent;
                    base._zoom_touches = e.touches;
                }
            }
        }
        base._zoomEnd = function(e){
            e.preventDefault();
            if(!base._zooming) return;
            base._zooming = false;
            let event = new $.Event('zoom_end', {
                target: base._zoom_object,
                oldTouches: base._zoom_touches
            });
            $(base._zoom_object).trigger(event);
            base._zoom_object = null;
            base._zoom_touches = null;
        }
        base._zoom = function (e){
            let
                zoomPointX = 0,
                zoomPointY = 0,
                zoomMultiplier = 1,
                zoomType = 'in';
            if(e.type === 'touchmove'){
                e.preventDefault();
                if(!base._zooming) return;

                let
                    touch1 = e.touches[0],
                    touch2 = e.touches[1],
                    oldTouch1 = base._zoom_touches[0],
                    oldTouch2 = base._zoom_touches[1],
                    distanceX = Math.abs(touch1.clientX - touch2.clientX),
                    distanceY = Math.abs(touch1.clientY - touch2.clientY);

                let offset = getOffset(base.$el);
                zoomPointX = Math.min(touch1.clientX, touch2.clientX) + (distanceX / 2) - offset.left;
                zoomPointY = Math.min(touch1.clientY, touch2.clientY) + (distanceY / 2) - offset.top;
                zoomMultiplier = (
                    getCursorDistance(
                        {x: touch1.clientX, y: touch1.clientY},
                        {x: touch2.clientX, y: touch2.clientY}
                    ) / getCursorDistance(
                        {x: oldTouch1.clientX, y: oldTouch1.clientY},
                        {x: oldTouch2.clientX, y: oldTouch2.clientY}
                    )
                );

                base._zoom_touches = e.touches;
                if(zoomMultiplier < 1){
                    zoomMultiplier = 1 + (1-zoomMultiplier);
                    zoomType = 'out';
                }else
                    zoomType = 'in';

                /*
                simulateDrag
                */
            }else if(e.type === 'wheel'){
                e.preventDefault();
                if(!$(e.target).closest('.zoomable').length) return;
                base._zoom_object = $(e.target).closest('.zoomable')[0];
                e = e.originalEvent;

                //let zoomIntensity = 0.2;
                //let wheel = e.deltaY < 0 ? 1 : -1;
                //let zoomMultiplier = Math.exp(wheel*zoomIntensity);
                zoomMultiplier = 1.2;
                zoomType = (e.wheelData ? e.wheelData : -e.deltaY) > 0 ? 'in' : 'out';

                let offset = getOffset(base.$el);
                zoomPointX = e.clientX - offset.left;// - parseInt($('.map-pane').css('margin-left'));
                zoomPointY = e.clientY - offset.top;// - parseInt($('.map-pane').css('margin-top'));
            }else if(e.type === 'zoomin'){
                zoomPointX = parseInt(base.$el.css('width'))/2;
                zoomPointY = parseInt(base.$el.css('height'))/2;
                zoomType = 'in';
                zoomMultiplier = 1.2;
                base._zoom_object = base.$el;
            }else if(e.type === 'zoomout'){
                zoomPointX = parseInt(base.$el.css('width'))/2;
                zoomPointY = parseInt(base.$el.css('height'))/2;
                zoomType = 'out';
                zoomMultiplier = 1.2;
                base._zoom_object = base.$el;
            }else{
                console.log('other zoom type');
                return;
            }
            let event = new $.Event('zoom', {
                target: base._zoom_object,
                zoomPointX: zoomPointX,
                zoomPointY: zoomPointY,
                zoomMultiplier: zoomMultiplier,
                zoomType: zoomType
            });
            base.onZoom(event);
            $(base._zoom_object).trigger(event);
        }

        base.init();
        return base;
    }
    $.zoomable.defaultOptions = {
        onZoom: function (e){

        }
    };
    $.fn.zoomable = function(options){
        let elements = [];
        this.each(function(){
            if(!$(this).data('zoomable'))
                elements.push(new $.zoomable(this, options));
            else
                elements.push($(this).data('zoomable'));
        });
        return elements.length === 1 ? elements[0] : elements;
    };
}(jQuery));