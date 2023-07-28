(function ($){
    $.resizable = function(el, options){
        console.log('construct');
        let base = this;
        base.$el = $(el);
        base.el = el;

        base.$el.data('resizable', base);
        console.log(base.$el);
        base.init = function (){
            base.options = $.extend({}, $.resizable.defaultOptions, options);
            base.$el.append('<div class="resizable-container"><div class="resizable-control"><span class="box-top-left"></span><span class="box-top-middle"></span><span class="box-top-right"></span><span class="box-middle-right"></span><span class="box-middle-left"></span><span class="box-bottom-left"></span><span class="box-bottom-middle"></span><span class="box-bottom-right"></span></div><div class="resizable-content"></div></div>');
            //base.$el.detach().appendTo(parent.find('.resizable-content'));
            base.$container = base.$el.find('.resizable-container');
            base.$controls = base.$el.find('.resizable-control');
            base.$content = base.$el.find('.resizable-content');
            base.$el.toggleClass('resizable', true);
        }

        base.getContainer = function (){
            return base.$container;
        }
        base.getControls = function (){
            return base.$controls;
        }
        base.getContent = function (){
            return base.$content;
        }
        base.destroyContent = function (){
            base.getContent()
                .empty()
                .css({});
            return base;
        }
        base.destroy = function (){
            let content = base.getContent().children().detach();
            base.$el.empty();
            base.$el.append(content);
            base.$el.toggleClass('resizable', false);
        }

        base.init();
        return base;
    }
    $.resizable.defaultOptions = {

    };
    $.fn.resizable = function(options){
        let elements = [];
        this.each(function(){
            if(!$(this).data('resizable'))
                elements.push(new $.resizable(this, options));
            else
                elements.push($(this).data('resizable'));
        });
        return elements.length === 1 ? elements[0] : elements;
    };
}(jQuery));