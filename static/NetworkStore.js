// secomdhcp/NetworkStore
define([
  'dojo/_base/declare',
  'dojo/_base/kernel',
  'dojo/_base/lang',
  'dojo/json',
  'secomdhcp/webapp'
], function(declare, kernel, lang, json, webapp){
  return declare(null, {
    items_by_id: {},

    _assertIsItem: function(/* item */ item){
      if (!this.isItem(item))
        throw new Error("NetworkStore: a function was passed an item argument that was not an item");
    },
    _assertIsAttribute: function(/* attribute-name-string */ attribute){
      if (typeof attribute !== "string")
        throw new Error("NetworkStore: a function was passed an attribute argument that was not an attribute name string");
    },

    getFeatures: function(){
      return {
        'dojo.data.api.Read': true,
        'dojo.data.api.Identity': true,
        'dojo.data.api.Notification': true
      };
    },

    getValue: function(item, attribute, defaultValue){
      var values = this.getValues(item, attribute);
      if (values && values.length > 0)
        return values[0];
      return defaultValue;
    },
    getValues: function(item, attribute){
      this._assertIsItem(item);
      this._assertIsAttribute(attribute);
      var value = item[attribute];
      if (typeof value !== 'undefined' && !(value instanceof Array))
        value = [value];
      else if (typeof value === 'undefined')
        value = [];
      return value;
    },

    getAttributes: function(item){
      return ['children', 'name'];
    },
    hasAttribute: function(item, attribute){
      this._assertIsItem(item);
      this._assertIsAttribute(attribute);
      return (attribute in item);
    },

    getIdentity: function(/* item */ item){
      return this.getValue(item, 'id');
    },
    getIdentityAttributes: function(item){
      return ['id'];
    },

    isItemLoaded: function(item){
      var loaded = this.isItem(item);
      if (loaded && typeof item._loaded == "boolean" && !item._loaded)
        loaded = false;
      return loaded;
    },

    loadItem: function(keywordArgs){
      var item = keywordArgs.item;
      var self = this;
      var scope = keywordArgs.scope || kernel.global;

      if (this.items_by_id[item.id]
      && typeof this.items_by_id[item.id]._loaded != "boolean") {
        if (keywordArgs.onItem)
          keywordArgs.onItem.call(scope, this.items_by_id[item.id]);
      } else {
        webapp.XhrJsonGet('/appdata/networktree2/' + item.id,
        {preventCache: true}).then(function(data){
          delete item._loaded;
          lang.mixin(item, data);
          self._processItem(item);
          if (keywordArgs.onItem)
            keywordArgs.onItem.call(scope, item);
        }, function(error){
          if(keywordArgs.onError)
            keywordArgs.onError.call(scope, error);
        });
      }
    },

    getLabel: function(item){
      return this.getValue(item, 'name');
    },
    getLabelAttributes: function(item){
      return ['name'];
    },

    containsValue: function(item, attribute, value){
      var values = this.getValues(item, attribute);
      for (var i = 0; i < values.length; i++) {
        if (values[i] == value)
          return true;
      }
      return false;
    },

    isItem: function(item){
      if (item && item._store === this)
        return true;
      return false;
    },

    close: function(request){
    },

    fetch: function(request){
      request = request || {};
      if (!request.store)
        request.store = this;
      var self = this;
      var scope = request.scope || kernel.global;

      //Generate what will be sent over.
      var reqParams = {};
      if (request.query)
        reqParams.query = json.stringify(request.query);

      if (request.sort)
        reqParams.sort = json.stringify(request.sort);

      if (request.queryOptions)
        reqParams.queryOptions = json.stringify(request.queryOptions);

      if (typeof request.start == "number")
        reqParams.start = "" + request.start;
      if (typeof request.count == "number")
        reqParams.count = "" + request.count;

      webapp.XhrJsonGet('/appdata/networktree2/network_root',
      {preventCache: true}).then(function(data){
        self._processResult(data, request);
      }, function(error){
        if (request.onError)
          request.onError.call(scope, error, request);
      });
    },

    fetchItemByIdentity: function(keywordArgs){
      var path = keywordArgs.identity;
      var self = this;
      var scope = keywordArgs.scope || kernel.global;

      if (this.items_by_id[path]
      && typeof this.items_by_id[path]._loaded != "boolean") {
        if (keywordArgs.onItem)
          keywordArgs.onItem.call(scope, this.items_by_id[path]);
      } else {
        webapp.XhrJsonGet('/appdata/networktree2/' + path,
        {preventCache: true}).then(function(data){
          var item = self._processItem(data);
          if (keywordArgs.onItem)
            keywordArgs.onItem.call(scope, item);
        }, function(error){
          if (keywordArgs.onError)
            keywordArgs.onError.call(scope, error);
        });
      }
    },

    _processResult: function(data, request){
      var scope = request.scope || kernel.global;
      try {
        if (request.onBegin)
          request.onBegin.call(scope, data.length, request);
        var items = this._processItemArray(data);
        if (request.onItem) {
          var i;
          for (i = 0; i < items.length; i++)
            request.onItem.call(scope, items[i], request);
          items = null;
        }
        if (request.onComplete)
          request.onComplete.call(scope, items, request);
      } catch (e) {
        if (request.onError)
          request.onError.call(scope, e, request);
        else
          console.log(e);
      }
    },
    _processItemArray: function(itemArray){
      var i;
      for (i = 0; i < itemArray.length; i++)
        this._processItem(itemArray[i]);
      return itemArray;
    },
    _processItem: function(item){
      if (!item)
        return null;
      item._store = this;
      if (item.children && (item.children instanceof Array)) {
        if (item.children.length > 0) {
          var children = item.children;
          var i;
          for (i = 0; i < children.length; i++ ) {
            if (children[i] instanceof Object)
              children[i] = this._processItem(children[i]);
          }
        } else
          item._loaded = false;
      }
      this.items_by_id[item.id] = item;
      return item;
    },

    close: function(){
      this.items_by_id = {};
    },

    newItem: function(item, parent){
      if (!this.isItem(parent))
        return;
      item = this._processItem(item);
      if (parent.children && (parent.children instanceof Array)) {
        var i = 0;
        for (i in parent.children) {
          if (parent.children[i].id > item.id)
            break;
        }
        parent.children.splice(i, 0, item);
      } else
        parent.children = [item];
      this.onNew(item, {'item': parent});
    },

    onNew: function(item, parentInfo){
    },
    onDelete: function(item){
    },
    onSet: function(item, attribute, oldValue, newValue){
    }
  });
});
