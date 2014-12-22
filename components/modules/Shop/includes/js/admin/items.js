// Generated by CoffeeScript 1.4.0

/**
 * @package   Shop
 * @category  modules
 * @author    Nazar Mokrynskyi <nazar@mokrynskyi.com>
 * @copyright Copyright (c) 2014, Nazar Mokrynskyi
 * @license   MIT License, see license.txt
*/


(function() {

  $(function() {
    var L, color_set_attribute_type, make_modal, set_attribute_types, string_attribute_types;
    L = cs.Language;
    set_attribute_types = [1, 2, 6, 9];
    color_set_attribute_type = [1, 2, 6, 9];
    string_attribute_types = [5];
    make_modal = function(attributes, categories, title, action) {
      var categories_list, modal;
      attributes = (function() {
        var attribute, attributes_;
        attributes_ = {};
        for (attribute in attributes) {
          attribute = attributes[attribute];
          attributes_[attribute.id] = attribute;
        }
        return attributes_;
      })();
      categories = (function() {
        var categories_, category;
        categories_ = {};
        for (category in categories) {
          category = categories[category];
          categories_[category.id] = category;
        }
        return categories_;
      })();
      categories_list = (function() {
        var categories_list_, category, key, keys, parent_category, _i, _len, _results;
        categories_list_ = {
          '-': "<option disabled>" + L.none + "</option>"
        };
        keys = ['-'];
        for (category in categories) {
          category = categories[category];
          parent_category = parseInt(category.parent);
          while (parent_category && parent_category !== category) {
            parent_category = categories[parent_category];
            if (parent_category.parent === category.id) {
              break;
            }
            category.title = parent_category.title + ' :: ' + category.title;
            parent_category = parseInt(parent_category.parent);
          }
          categories_list_[category.title] = "<option value=\"" + category.id + "\">" + category.title + "</option>";
          keys.push(category.title);
        }
        keys.sort();
        _results = [];
        for (_i = 0, _len = keys.length; _i < _len; _i++) {
          key = keys[_i];
          _results.push(categories_list_[key]);
        }
        return _results;
      })();
      categories_list = categories_list.join('');
      modal = $.cs.simple_modal("<form>\n	<h3 class=\"cs-center\">" + title + "</h3>\n	<p>\n		" + L.shop_category + ": <select name=\"category\" required>" + categories_list + "</select>\n	</p>\n	<div></div>\n</form>");
      modal.item_data = {};
      modal.update_item_data = function() {
        var attribute, item, value, _ref;
        item = modal.item_data;
        console.log(item);
        modal.find('[name=price]').val(item.price);
        modal.find('[name=in_stock]').val(item.in_stock);
        modal.find("[name=soon][value=" + item.soon + "]").prop('checked', true);
        modal.find("[name=listed][value=" + item.listed + "]").prop('checked', true);
        if (item.images) {
          modal.add_images(item.images);
        }
        if (item.attributes) {
          _ref = item.attributes;
          for (attribute in _ref) {
            value = _ref[attribute];
            modal.find("[name='attributes[" + attribute + "]']").val(value);
          }
        }
        if (item.tags) {
          return modal.find('[name=tags]').val(item.tags.join(', '));
        }
      };
      modal.find('[name=category]').change(function() {
        var $this, attributes_list, category, images_container, uploader;
        modal.find('form').serializeArray().forEach(function(item) {
          var attribute, name, value;
          value = item.value;
          name = item.name;
          switch (name) {
            case 'tags':
              value = value.split(',').map(function(v) {
                return $.trim(v);
              });
              break;
            case 'images':
              if (value) {
                value = JSON.parse(value);
              }
          }
          if (attribute = name.match(/attributes\[([0-9]+)\]/)) {
            if (!modal.item_data.attributes) {
              modal.item_data.attributes = {};
            }
            return modal.item_data.attributes[attribute[1]] = value;
          } else {
            return modal.item_data[item.name] = value;
          }
        });
        $this = $(this);
        category = categories[$this.val()];
        attributes_list = (function() {
          var attribute, color, values, _i, _len, _ref, _results;
          _ref = category.attributes;
          _results = [];
          for (_i = 0, _len = _ref.length; _i < _len; _i++) {
            attribute = _ref[_i];
            attribute = attributes[attribute];
            attribute.type = parseInt(attribute.type);
            if (set_attribute_types.indexOf(attribute.type) !== -1) {
              values = (function() {
                var value, _j, _len1, _ref1, _results1;
                _ref1 = attribute.value;
                _results1 = [];
                for (_j = 0, _len1 = _ref1.length; _j < _len1; _j++) {
                  value = _ref1[_j];
                  _results1.push("<option value=\"" + value + "\">" + value + "</option>");
                }
                return _results1;
              })();
              values = ("<option value=\"\">" + L.none + "</option>") + values.join('');
              color = attribute.type === color_set_attribute_type ? "<input type=\"color\">" : "";
              _results.push("<p>\n	" + attribute.title + ": <select name=\"attributes[" + attribute.id + "]\">" + values + "</select> " + color + "\n</p>");
            } else if (string_attribute_types.indexOf(attribute.type) !== -1) {
              _results.push("<p>\n	" + attribute.title + ": <input name=\"attributes[" + attribute.id + "]\">\n</p>");
            } else {
              _results.push("<p>\n	" + attribute.title + ": <textarea name=\"attributes[" + attribute.id + "]\"></textarea>\n</p>");
            }
          }
          return _results;
        })();
        attributes_list = attributes_list.join('');
        $this.parent().next().html("<p>\n	" + L.shop_price + ": <input name=\"price\" type=\"number\" value=\"0\" required>\n</p>\n<p>\n	" + L.shop_in_stock + ": <input name=\"in_stock\" type=\"number\" value=\"1\" step=\"1\">\n</p>\n<p>\n	" + L.shop_available_soon + ":\n	<label><input type=\"radio\" name=\"soon\" value=\"1\"> " + L.yes + "</label>\n	<label><input type=\"radio\" name=\"soon\" value=\"0\" checked> " + L.no + "</label>\n</p>\n<p>\n	" + L.shop_listed + ":\n	<label><input type=\"radio\" name=\"listed\" value=\"1\" checked> " + L.yes + "</label>\n	<label><input type=\"radio\" name=\"listed\" value=\"0\"> " + L.no + "</label>\n</p>\n<p>\n	<div class=\"images\"></div>\n	<button type=\"button\" class=\"add-images uk-button\">" + L.shop_add_images + "</button>\n	<input type=\"hidden\" name=\"images\">\n</p>\n" + attributes_list + "\n<p>\n	" + L.shop_tags + ": <input name=\"tags\" placeholder=\"shop, high quality, e-commerce\">\n</p>\n<p>\n	<button class=\"uk-button\" type=\"submit\">" + action + "</button>\n</p>");
        images_container = modal.find('.images');
        modal.update_images = function() {
          var images;
          images = [];
          images_container.find('a').each(function() {
            return images.push($(this).attr('href'));
          });
          modal.find('[name=images]').val(JSON.stringify(images));
          if (images.length > 1) {
            return images_container.sortable({
              placeholder: '<span>&nbsp;</span>',
              forcePlaceholderSize: true
            }).on('sortupdate', modal.update_images);
          } else {
            return images_container.sortable('destroy');
          }
        };
        modal.add_images = function(images) {
          images.forEach(function(image) {
            return images_container.append("<span>\n	<a href=\"" + image + "\" target=\"_blank\" class=\"uk-thumbnail uk-thumbnail-mini\">\n		<img src=\"" + image + "\">\n		<br>\n		<button type=\"button\" class=\"remove-image uk-button uk-button-danger uk-button-mini uk-width-1-1\">" + L.shop_remove_image + "</button>\n	</a>\n</span>");
          });
          return modal.update_images();
        };
        if (cs.file_upload) {
          uploader = cs.file_upload(modal.find('.add-images'), function(images) {
            return modal.add_images(images);
          }, function(error) {
            return alert(error.message);
          }, null, true);
          modal.on('uk.modal.hide', function() {
            return uploader.destroy();
          });
        } else {
          modal.find('.add-images').click(function() {
            var image;
            image = prompt(L.shop_image_url);
            if (image) {
              return modal.add_images([image]);
            }
          });
        }
        modal.on('click', '.remove-image', function() {
          $(this).parent().remove();
          modal.update_images();
          return false;
        });
        return modal.update_item_data();
      });
      return modal;
    };
    return $('html').on('mousedown', '.cs-shop-item-add', function() {
      return $.when($.getJSON('api/Shop/admin/attributes'), $.getJSON('api/Shop/admin/categories')).done(function(attributes, categories) {
        var modal;
        modal = make_modal(attributes[0], categories[0], L.shop_item_addition, L.shop_add);
        return modal.find('form').submit(function() {
          $.ajax({
            url: 'api/Shop/admin/items',
            type: 'post',
            data: $(this).serialize(),
            success: function() {
              alert(L.shop_added_successfully);
              return location.reload();
            }
          });
          return false;
        });
      });
    }).on('mousedown', '.cs-shop-item-edit', function() {
      var id;
      id = $(this).data('id');
      return $.when($.getJSON('api/Shop/admin/attributes'), $.getJSON('api/Shop/admin/categories'), $.getJSON("api/Shop/admin/items/" + id)).done(function(attributes, categories, item) {
        var modal;
        modal = make_modal(attributes[0], categories[0], L.shop_item_edition, L.shop_edit);
        modal.find('form').submit(function() {
          $.ajax({
            url: "api/Shop/admin/items/" + id,
            type: 'put',
            data: $(this).serialize(),
            success: function() {
              alert(L.shop_edited_successfully);
              return location.reload();
            }
          });
          return false;
        });
        modal.item_data = item[0];
        return modal.find("[name=category]").val(item[0].category).change();
      });
    }).on('mousedown', '.cs-shop-item-delete', function() {
      var id;
      id = $(this).data('id');
      if (confirm(L.shop_sure_want_to_delete)) {
        return $.ajax({
          url: "api/Shop/admin/items/" + id,
          type: 'delete',
          success: function() {
            alert(L.shop_deleted_successfully);
            return location.reload();
          }
        });
      }
    });
  });

}).call(this);
