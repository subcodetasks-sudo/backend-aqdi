<div class="container">
  <center>
    <h1 style="color: rgb(35, 179, 129)">إحصائياتنا</h1>
    <br><br>
  </center>

  <div class="row">
    @foreach ($overview as $item)
    <div class="col-md-2 mb-3">
      <div class="wrapper" style="padding: 7%">
        <div class="container">
          <i><img src="{{ Storage::url($item->image) }}" style="width: 40px; height: 40px; object-fit: cover;" alt=""></i>
          <span class="num" data-val="{{ $item->value }}">000</span>
          <span class="text">{{ $item->name_overview }}</span>
        </div>
      </div>
    </div>
    @endforeach
</div>


<!-- Script -->
<script src="script.js"></script>

<script>
  document.addEventListener("DOMContentLoaded", function() {
    let valueDisplays = document.querySelectorAll(".num");
    let interval = 4000;

    valueDisplays.forEach((valueDisplay) => {
      let startValue = 0;
      let endValue = parseInt(valueDisplay.getAttribute("data-val"));
      let duration = Math.floor(interval / endValue);
      let counter = setInterval(function () {
        startValue += 1;
        valueDisplay.textContent = startValue.toLocaleString();  
        if (startValue === endValue) {
          clearInterval(counter);
        }
      }, duration);
    });
  });
</script>

<style>
  .row {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-around;
  }

  .col-md-4 {
    flex: 0 0 calc(33.33% - 1rem);
    max-width: calc(33.33% - 1rem);
    padding: 0 0.5rem;
  }

  .wrapper {
    padding: 7%;
    text-align: center;
  }

  .container i {
    font-size: 36px;
    color: #369075;
  }

  .num {
    font-size: 48px;
    font-weight: 600;
    color: #333;
    display: block;
    margin: 10px 0;
  }

  .text {
    font-size: 18px;
    color: #666;
  }
</style>
