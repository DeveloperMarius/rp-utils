(function ($){

    function simulateDrag(drag_start){
        let event = new $.Event('drag_move', {
            clientX: e.clientX,
            clientY: e.clientY,
            target: drag_object,
            drag_start: drag_start,
            startX: drag_start.x,
            startY: drag_start.y
        });
        $(drag_object).trigger(event);
        if(!event.isDefaultPrevented()){

        }
    }

    $.draganddrop = function(el, options){
        let base = this;
        base.$el = $(el);
        base.el = el;

        base.$el.data('draganddrop', base);

        base.init = function (){
            base.options = $.extend({}, $.draganddrop.defaultOptions, options);
            base._dragging = false;
            base._drag_object = null;
            base._drag_object_clicked = null;
            base._drag_start = {x: 0, y: 0};

            if(base.options.duplicate !== undefined && base.options.duplicate){
                base.$el.parent().append(base.$el[0].outerHTML);
            }

            if(base.options.onStart !== undefined)
                base.onStart = base.options.onStart;
            if(base.options.onMove !== undefined)
                base.onMove = base.options.onMove;
            if(base.options.onEnd !== undefined)
                base.onEnd = base.options.onEnd;

            base.$el
                .on('mousedown', function(e){
                    base._dragStart(e);
                })
                .on('touchstart', function(e){
                    e.preventDefault();
                    let fingers = e.originalEvent.touches.length;
                    if(fingers === 1)
                        base._dragStart(e);
                    else if(fingers === 2){
                        base._dragEnd(e);
                    }
                });
            $('body')
                .on('mousemove', function(e){
                    base._dragMove(e);
                })
                .on('touchmove', function (e){
                    let fingers = e.originalEvent.touches.length;
                    if(fingers === 1)
                        base._dragMove(e);
                })
                .on('mouseup', function(e){
                    base._dragEnd(e);
                })
                .on('touchend', function(e){
                    e.preventDefault();
                    let event = e.originalEvent;
                    if((event.changedTouches.length === 1 && event.touches.length === 0) && base._dragging)
                        base._dragEnd(e);
                })
                .on('touchcancel', function(e){
                    base._dragEnd(e);
                });
            base.$el.toggleClass('draganddrop', true);
        }

        base._dragStart = function(e){
            e.preventDefault();
            if($(e.target).closest('.draganddrop').length){
                e = e.originalEvent;
                if(e.type === 'touchstart'){
                    if(e.touches.length === 0) return;
                    e = e.touches[0];
                }
                base._drag_object = $(e.target).closest('.draganddrop')[0];
                base._drag_start.x = e.clientX;
                base._drag_start.y = e.clientY;
                base._drag_object_clicked = e.target;
                //$('.map').css('cursor', 'inherit');
                let event = new $.Event('drag_start', {
                    clientX: e.clientX,
                    clientY: e.clientY,
                    target: base._drag_object,
                    clickedTarget: base._drag_object_clicked,
                    startX: base._drag_start.x,
                    startY: base._drag_start.y
                });
                $(base._drag_object).trigger(event);
                base.onStart(event);
                if(!event.isDefaultPrevented()){
                    base._dragging = true;
                }
            }
        }
        base._dragMove = function(e){
            e.preventDefault();
            if(!base._dragging) return;
            e = e.originalEvent;
            if(e.type === 'touchmove'){
                if(e.touches.length !== 1) return;
                e = e.touches[0];
            }
            let event = new $.Event('drag_move', {
                clientX: e.clientX,
                clientY: e.clientY,
                target: base._drag_object,
                drag_start: base._drag_start,
                clickedTarget: base._drag_object_clicked,
                startX: base._drag_start.x,
                startY: base._drag_start.y
            });
            $(base._drag_object).trigger(event);
            base.onMove(event);
            if(!event.isDefaultPrevented()){

            }
        }
        base._dragEnd = function(e){
            e.preventDefault();
            if(!base._dragging) return;
            e = e.originalEvent;
            if(e.type === 'touchend' || e.type === 'touchcancel' || e.type === 'touchstart'){
                if(e.touches.length !== 0 && e.touches.length !== 2) return;
                if(e.changedTouches.length === 0) return;
                e = e.changedTouches[0];
            }
            let event = new $.Event('drag_end', {
                clientX: e.clientX,
                clientY: e.clientY,
                target: base._drag_object,
                clickedTarget: base._drag_object_clicked,
                container: e.target,
                startX: base._drag_start.x,
                startY: base._drag_start.y
            });
            $(base._drag_object).trigger(event);
            base.onEnd(event);
            if(!event.isDefaultPrevented()){
                base._dragging = false;
                base._drag_object = null;
                base._drag_object_clicked = null;
                base._drag_start = {x: 0, y: 0};
            }
        }

        base.init();
        return base;
    }
    $.draganddrop.defaultOptions = {
        duplicate: false,
        onStart: function (e){

        },
        onMove: function (e){

        },
        onEnd: function (e){

        }
    };
    $.fn.draganddrop = function(options){
        let elements = [];
        this.each(function(){
            if(!$(this).data('draganddrop'))
                elements.push(new $.draganddrop(this, options));
            else
                elements.push($(this).data('draganddrop'));
        });
        return elements.length === 1 ? elements[0] : elements;
    };
}(jQuery));