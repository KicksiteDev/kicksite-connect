const kicksiteCalendar = document.querySelector(".kicksite-calendar");

if (kicksiteCalendar) {
  const wizardTrack = kicksiteCalendar.querySelector(".ks-calendar-track");
  const tableBody = kicksiteCalendar.querySelector("tbody");
  const classesContainer = kicksiteCalendar.querySelector(
    ".ks-calendar-classes",
  );

  const dataURL = kicksiteCalendar.dataset.ajaxUrl ?? "";
  const dataNonce = kicksiteCalendar.dataset.nonce ?? "";
  const dataProgramIds = kicksiteCalendar.dataset.programIds ?? "";
  const dataSubdomain = kicksiteCalendar.dataset.subdomain ?? "";

  const state = {
    year: new Date().getFullYear(),
    month: new Date().getMonth(),
    selectedDay: null,
    selectedClass: null,
    calendarData: [],
  };

  /**
   * Send a fetch request to admin ajax which tells WordPress to initiate a get request
   * to Kicksite. If succesful, it will return the calendar data for the school. If the
   * data is already cached, it retrieves that instead of making a new downstream call
   * to the Kicksite API.
   * @param {*} year
   * @param {*} month
   */
  function fetchMonth(year, month) {
    fetch(dataURL, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: new URLSearchParams({
        year: year,
        month: month + 1,
        program_ids: dataProgramIds,
        nonce: dataNonce,
        action: "kicksite_get_calendar",
      }),
    })
      .then((response) => response.json())
      .then(({ data }) => {
        state.calendarData = data;
        state.year = year;
        state.month = month;
        renderMonthGrid();
      })
      .catch((error) => console.error("Error:", error));
  }

  /**
   * Renders the full month grid based on the data that is returned from running the
   * fetchMonth function.
   */
  function renderMonthGrid() {
    // Build a new array based on classes that only allow membership registration.
    // Separate the full time to return only the y-m-d.
    const ksClassDates = state.calendarData
      .filter((data) => data.allow_member_registration)
      .map((data) => data.start_time.split("T", 1)[0]);

    // Filter the array so only the unique values remain
    const uniqueDates = ksClassDates.filter((item, index) => {
      return ksClassDates.indexOf(item) === index;
    });
    const monthStart = new Date(state.year, state.month, 1).getDay();
    const totalDays = new Date(state.year, state.month + 1, 0).getDate();
    const totalCells = Math.ceil((monthStart + totalDays) / 7) * 7;
    let currentRow;

    tableBody.innerHTML = "";
    // Set up the initial calendar layout based on the current month
    for (let index = 0; index < totalCells; index++) {
      const dayNumber = index - monthStart + 1;
      const monthPad = String(state.month + 1).padStart(2, 0);
      const dayPad = String(dayNumber).padStart(2, 0);
      const td = document.createElement("td");
      const span = document.createElement("span");

      if (index < monthStart || dayNumber > totalDays) {
        td.classList.add("ks-day--empty");
      } else {
        td.classList.add("ks-day");
        td.dataset.date = `${state.year}-${monthPad}-${dayPad}`;
        span.textContent = dayNumber;
        td.appendChild(span);
      }

      if (index % 7 === 0) {
        currentRow = tableBody.insertRow();
      }
      currentRow.appendChild(td);
    }

    // Query through all of the available days on the calendar, if any of the
    // days dataset date attribute match one of the unique dates from the
    // uniqueDates array, replace the class with ks-day--active.
    const calDays = kicksiteCalendar.querySelectorAll(".ks-day");
    calDays.forEach((day) => {
      if (uniqueDates.includes(day.dataset.date)) {
        day.classList.replace("ks-day", "ks-day--active");
      }
    });
  }

  /**
   * Fetch the month calendar data
   */
  fetchMonth(state.year, state.month);

  /**
   * When a user clicks on a date that has available classes, render the html
   * for step 2 of the wizard.
   * This function is fired by the table body click event delegation below.
   * @param {*} day
   */
  function showDayClasses(day) {
    const classes = kicksiteCalendar.querySelector(".ks-cal-class-container");
    const availableClasses = state.calendarData.filter(
      (data) =>
        data.allow_member_registration && data.start_time.startsWith(day),
    );

    classes.innerHTML = "";
    availableClasses.forEach((cls) => {
      const classTime = formatTime(cls.start_time);
      const classHtml = `
        <div class="ks-classes" data-class-id="${cls.id}" data-class-time="${cls.start_time}">
          <div class="ks-class-title">${cls.name}</div>
          <div class="ks-class-program">${cls.program.name}</div>
          <div class="ks-class-time-slot">${classTime}</div>
        </div>
      `;
      classes.insertAdjacentHTML("beforeend", classHtml);
    });
  }

  /**
   * When a user selects a class from the available classes in step 2 of the wizard,
   * match the selected class with the corresponding class from state.calendarData.
   * After finding a match, update state.selectedClass and then render the heading
   * for the form.
   * This function is fired by the class container click event delegation below.
   * @param {*} cls
   */
  function selectClass(cls) {
    const ksFormHeading = kicksiteCalendar.querySelector(
      ".ks-cal-form-heading",
    );
    const matchClass = state.calendarData.filter(
      (data) =>
        data.start_time === cls.dataset.classTime &&
        data.id === parseInt(cls.dataset.classId),
    );

    state.selectedClass = matchClass[0];

    ksFormHeading.innerHTML = `
      <h4>${state.selectedClass.name}</h4>
      <strong>${state.selectedClass.program.name}</strong>
      <div>${formatTime(state.selectedClass.start_time)}</div>
    `;

    advanceWizard("forward");
  }

  let n = 0;
  // Table body click event delegation that fires an event to traverse up the DOM to
  // find the closest ks-day--active class. If it finds that class, set the state for
  // the selectedDay to date from that element's dataset date.
  tableBody.addEventListener("click", (e) => {
    const day = e.target.closest(".ks-day--active");
    if (day) {
      state.selectedDay = day.dataset.date;
      showDayClasses(state.selectedDay);
      advanceWizard("forward");
    }
  });

  // Classes container click event delegation that fires an event to traverse up the DOM
  // to find the closest .ks-classes class. If it finds that class, fire the selectClass
  // function.
  classesContainer.addEventListener("click", (e) => {
    const selectedClass = e.target.closest(".ks-classes");
    if (selectedClass) {
      selectClass(selectedClass);
    }
  });

  // Click event for the nav back button on each wizard step
  const navBack = kicksiteCalendar.querySelectorAll(".ks-calendar-nav-back");
  navBack.forEach((nav) => {
    nav.addEventListener("click", () => {
      advanceWizard("back");
    });
  });

  // Advance the wizard forward or backward depending on the parameter set in the click event
  function advanceWizard(direction) {
    if (direction === "forward") {
      if (n >= 2) return;
      n++;
      wizardTrack.style.transform = `translateX(-${n * 33.333}%)`;
    } else {
      if (n <= 0) return;
      n--;
      wizardTrack.style.transform = `translateX(${-n * 33.333}%)`;
    }
  }

  /**
   * Formats times into a readable string
   * @param {*} isoString
   */
  function formatTime(isoString) {
    return new Date(isoString).toLocaleTimeString([], {
      hour: "numeric",
      minute: "2-digit",
    });
  }
}
