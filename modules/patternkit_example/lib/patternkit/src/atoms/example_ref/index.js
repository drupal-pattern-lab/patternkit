(function () {
  'use strict';

  import './src/example.scss';
  import example from './dist/example';

  var element = Object.create(HTMLElement.prototype);
  element.createdCallback = function () {};
  element.attachedCallback = function () {};
  element.detachedCallback = function () {};
  element.attributeChangedCallback = function (attr, oldVal, newVal) {};
  document.registerElement('example', {
    prototype: element
  });
}());
