/**
  *  The MIT License (MIT)
  *
  *  Copyright (c) 2013 Actual Reports
  *
  *  Permission is hereby granted, free of charge, to any person obtaining a copy of
  *  this software and associated documentation files (the "Software"), to deal in
  *  the Software without restriction, including without limitation the rights to
  *  use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of
  *  the Software, and to permit persons to whom the Software is furnished to do so,
  *  subject to the following conditions:
  *
  *  The above copyright notice and this permission notice shall be included in all
  *  copies or substantial portions of the Software.
  *
  *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
  *  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
  *  FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
  *  COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
  *  IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
  *  CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
  *
  **/
(function($) {
  var AR = function(container, options)
  {
    this.id = 'actual_reports_toolbar_'+(new Date().getTime());
    this.container = container;
    this.options = $.extend({}, this.defaults, options);

    if (this.options.showToolbar === false)
    {
      this.container.hide();
    }
    else
    {
      this.addButtonHandlers();
    }
    this.loadTemplates();
    this.createModal();
  };

  $.extend(AR.prototype, {
    defaults: {
      endpoints: {
        editor: '?action=editor',
        templates: '?action=templates',
        inline: '?action=inline',
        download: '?action=download',
        print: '?action=print'
      }
    },

    addButtonHandlers: function()
    {
      var self = this;

      self.buttons = {};
      $.each(['create', 'edit', 'preview', 'print', 'download'], function(index, val) {
        self.buttons[val] = self.container.find('[data-action="'+val+'"]').on('click', function(event) {
          var el = $(this);
          var templateId = self.getTemplateId();

          switch(el.data('action'))
          {
            case 'create':
              self.showEditor();
              break;
            case 'edit':
              self.showEditor(true);
              break;
            case 'preview':
              self.preview();
              break;
            case 'print':
              self.print();
              break;
            case 'download':
              self.download(el.data('type'));
              break;
          }

          event.preventDefault();
          return false;
        });
      });
    },

    preview: function()
    {
      this.showTemplateInModal(this.options.endpoints.inline, 'pdf', 'preview');
    },

    print: function()
    {
      this[this.isSafari() ? 'showTemplateInModal' : 'loadTempalteToIframe'](this.options.endpoints.print, 'pdf', 'print');
    },

    download: function(format)
    {
      this.loadTempalteToIframe(this.options.endpoints.download, format, 'download');
    },

    isSafari: function()
    {
      return $.browser.webkit && !window.chrome;
    },

    loadTemplates: function(refresh)
    {
      var select = this.container.find('select[data-action="templates"]');
      var selectedValue = select.val();

      select.empty();
      $.getJSON(this.options.endpoints.templates, {
        actual_reports_breakcache: refresh === true ? 1 : 0
      }, function(templates) {
        var custom = $(document.createElement('optgroup')).attr('label', 'Custom').appendTo(select);
        var defaults = $(document.createElement('optgroup')).attr('label', 'Default').appendTo(select);
        $.each(templates, function(index, t) {
          (t.owner ? custom : defaults).append($(document.createElement('option')).attr('value', t.id).text(t.name));
        });
        select.val(selectedValue);
      });
    },

    getTemplateId: function()
    {
      return +this.container.find('select[data-action="templates"]').val();
    },

    createModal: function()
    {
      var body = $('body');
      $(document.createElement('style')).attr('type', 'text/css').html('a.ar-close-btn{position:absolute;top:5px;right:10px;cursor:pointer;color:#fff;border:1px solid #AEAEAE;border-radius:30px;background:#605F61;font-size:31px;font-weight:700;display:inline-block;line-height:0;padding:11px 3px}.ar-close-btn:before{content:"×"}').appendTo($('head'));

      this.overlay = $(document.createElement('div')).css({
        'display': 'none',
        'position': 'fixed',
        'z-index': 2000,
        'top': 0,
        'left': 0,
        'height': '100%',
        'width': '100%',
        'background': '#333',
        'opacity': 0.5
      }).appendTo(body).on('click', $.proxy(this.hideModal, this));

      this.modal = $(document.createElement('div')).css({
        display: 'none',
        margin: 'auto',
        position: 'absolute',
        'z-index': 2001,
        top: 0,
        left: 0,
        bottom: 0,
        right: 0,
        overflow: 'hidden',
        'background-color': '#ededed'
      }).appendTo(body);

      $(document.createElement('a')).addClass('ar-close-btn').on('click', $.proxy(function() {
        this.hideModal();
      }, this)).appendTo(this.modal);

      this.iframe = $(document.createElement('iframe')).attr({
        id: this.getIframeId(),
        name: this.getIframeName()
      }).css({
        border: '0px',
        width: '100%',
        height: '100%',
        'background-color': '#fff'
      }).appendTo(this.modal);

      this.form = $(document.createElement('form')).attr({
        method: 'post',
        target: this.getIframeName()
      })
      .css({
        display: 'none'
      })
      .appendTo(this.modal);

      $(document).keydown($.proxy(function(e) {
        if (e.keyCode == 27) {
          this.hideModal();
        }
      }, this));
    },

    getIframeId: function()
    {
      return this.id+'_iframe';
    },

    getIframeName: function()
    {
      return this.getIframeId()+'_name';
    },

    /**
     * Opens editor
     * NB! Posts directly to Actual Reports API /api/v2/editor
     *
     * @param  {Boolean} addTemplate
     */
    showEditor: function(addTemplate)
    {
      var form = this.form;
      // If beforeRequest function returs false then cancel submit
      if (!this.beforeRequest({action: 'editor'}))
      {
        return ;
      }

      $.getJSON(this.options.endpoints.editor, {
        actual_reports_params: JSON.stringify(this.extraParams)
      }, $.proxy(function(data) {
        // Add template id to params
        if (addTemplate)
        {
          data.params.template = this.getTemplateId();
        }

        form.empty();
        $.each(data.params, function(key, val) {
          $(document.createElement('input')).attr({
            type: 'text',
            name: key,
            value: val
          }).appendTo(form);
        });

        form.attr('action', data.url).submit();
        this.iframe.css('marginTop', '0px');
        this.showModal();
      }, this));
    },

    showTemplateInModal: function(url, format, action)
    {
      if (!this.beforeRequest({action: action, func: 'showTemplateInModal'}))
      {
        return ;
      }
      this.iframe.css('marginTop', '35px');
      this.loadTempalteToIframe(url, format);
      this.showModal();
    },

    loadTempalteToIframe: function(url, format, action)
    {
      if (!this.beforeRequest({action: action, func: 'loadTempalteToIframe'}))
      {
        return ;
      }

      url += (url[0] !== '?' ? '?' : '&')+['actual_reports_template='+this.getTemplateId(), 'actual_reports_format='+format, 'actual_reports_params='+JSON.stringify(this.extraParams)].join('&')
      this.iframe.attr('src', url);
    },

    showModal: function()
    {
      var win = $(window);
      this.overlay.fadeIn(200);
      this.modal.css({
        display: 'block',
        height: win.height()*0.95,
        width: win.width()*0.95
      });
    },

    hideModal: function()
    {
      this.iframe.attr('src', 'about:blank');
      this.overlay.fadeOut(200);
      this.modal.css('display', 'none');
      this.loadTemplates(true);
    },

    setExtraParams: function(params)
    {
      this.extraParams = params;
    },

    beforeRequest: function(params)
    {
      if (typeof this.options.beforeRequest === 'function')
      {
        return this.options.beforeRequest.call(this.options.scope || this.container, this.container, params, this);
      }

      return true;
    }
  });

  $.fn.actualreports = function(options)
  {
    var args = Array.prototype.slice.call(arguments);
    this.each(function(index, container) {
      container = $(container);

      if (!container.data('ar-instance'))
      {
        container.data('ar-instance', new AR(container, options));
      }
      else
      {
        var instance = container.data('ar-instance');
        var publicMethods = ['create', 'edit', 'hide', 'print', 'preview', 'download', 'params'];
        if (typeof args[0] === 'string' && $.inArray(args[0], publicMethods))
        {

          switch (args[0])
          {
            case 'create':
              instance.showEditor();
              break;
            case 'edit':
              instance.showEditor(true);
              break;
            case 'print':
              instance.print();
              break;
            case 'preview':
              instance.print();
              break;
            case 'download':
              instance.download(args[1] || 'pdf');
              break;
            case 'hide':
              instance.hideModal();
              break;
            case 'params':
              instance.setExtraParams(args[1]);
          }
        }
      }
    });
  };
})(jQuery);